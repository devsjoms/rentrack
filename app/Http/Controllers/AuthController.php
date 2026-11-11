<?php

namespace App\Http\Controllers;

use App\Mail\LoginOtpMail;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    // STEP 1: Validate username and password.
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ]);

        $account = Account::where('username', $data['username'])
            ->first();

        if (
            !$account ||
            !Hash::check($data['password'], $account->password_hash)
        ) {
            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        if (!in_array($account->role, ['admin', 'tenant'], true)) {
            throw ValidationException::withMessages([
                'username' => 'Account unavailable.',
            ]);
        }

        $profile = $account->role === 'admin'
            ? $account->admin
            : $account->tenant;

        if (
            !$profile ||
            strtolower((string) $profile->status) !== 'active'
        ) {
            throw ValidationException::withMessages([
                'username' => 'Account inactive or unavailable.',
            ]);
        }

        if (!filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'username' => 'Account email is not configured.',
            ]);
        }

        // Clear any previous authentication session.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Create and send a new OTP.
        $otpId = $this->issueOtp($account);

        // Store temporary pending-login state.
        // The account is NOT authenticated yet.
        $request->session()->put([
            'pending_2fa_account_id' => $account->user_id,
            'pending_2fa_otp_id' => $otpId,
            'pending_2fa_expires_at' => now()->addMinutes(5)->timestamp,
            'pending_2fa_attempts' => 0,
            'pending_2fa_resend_after' => now()->addSeconds(60)->timestamp,
        ]);

        return redirect()->route('otp.form')
            ->with('status', 'Verification code generated.');
    }

    // Generate, hash, store and send OTP.
    private function issueOtp(Account $account): int
    {
        $limitKey = 'rentrack-otp-send:' . $account->user_id;

        // Maximum 3 OTP sends in 15 minutes per account.
        if (RateLimiter::tooManyAttempts($limitKey, 3)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many OTP requests. Please try again later.',
            ]);
        }

        RateLimiter::hit($limitKey, 900);

        $code = (string) random_int(100000, 999999);

        $otpId = DB::transaction(function () use ($account, $code) {

            // Expire previously unused active OTPs.
            DB::table('otp_verifications')
                ->where('user_id', $account->user_id)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->update([
                    'expires_at' => now(),
                ]);

            return DB::table('otp_verifications')->insertGetId([
                'user_id' => $account->user_id,
                'otp_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(5),
                'verified_at' => null,
                'created_at' => now(),
            ], 'otp_id');
        });

        try {
            Mail::to($account->email)->send(
                new LoginOtpMail($code)
            );
        } catch (Throwable $exception) {

            // Invalidate this OTP if email sending fails.
            DB::table('otp_verifications')
                ->where('otp_id', $otpId)
                ->update(['expires_at' => now()]);

            report($exception);

            throw ValidationException::withMessages([
                'otp' => 'Unable to send verification code. Try again later.',
            ]);
        }

        return (int) $otpId;
    }

    private function hasValidPendingLogin(Request $request): bool
    {
        return $request->session()->has('pending_2fa_account_id')
            && $request->session()->has('pending_2fa_otp_id')
            && (int) $request->session()->get(
                'pending_2fa_expires_at',
                0
            ) > now()->timestamp;
    }

    private function clearPendingLogin(Request $request): void
    {
        $request->session()->forget([
            'pending_2fa_account_id',
            'pending_2fa_otp_id',
            'pending_2fa_expires_at',
            'pending_2fa_attempts',
            'pending_2fa_resend_after',
        ]);
    }

    // STEP 2: Display the OTP form.
    public function showOtpForm(Request $request)
    {
        if (!$this->hasValidPendingLogin($request)) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')
                ->withErrors([
                    'otp' => 'Verification session expired. Please log in again.',
                ]);
        }

        return view('auth.verify-otp');
    }

    // STEP 3: Verify the submitted OTP.
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        if (!$this->hasValidPendingLogin($request)) {
            $this->clearPendingLogin($request);

            return redirect()->route('login');
        }

        if ((int) $request->session()->get('pending_2fa_attempts', 0) >= 5) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')
                ->withErrors(['otp' => 'Too many attempts. Log in again.']);
        }

        $accountId = $request->session()->get('pending_2fa_account_id');
        $otpId = $request->session()->get('pending_2fa_otp_id');

        $otpRecord = DB::table('otp_verifications')
            ->where('otp_id', $otpId)
            ->where('user_id', $accountId)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')
                ->withErrors(['otp' => 'OTP expired or already used.']);
        }

        $attempts = $request->session()->increment(
            'pending_2fa_attempts'
        );

        if (!Hash::check($data['otp'], $otpRecord->otp_hash)) {
            if ($attempts >= 5) {
                $this->clearPendingLogin($request);

                return redirect()->route('login')
                    ->withErrors([
                        'otp' => 'Too many incorrect attempts. Log in again.',
                    ]);
            }

            return back()->withErrors([
                'otp' => 'Incorrect verification code.',
            ]);
        }

        $account = Account::find($accountId);

        if (!$account || !in_array($account->role, ['admin', 'tenant'], true)) {
            $this->clearPendingLogin($request);
            return redirect()->route('login');
        }

        $profile = $account->role === 'admin'
            ? $account->admin
            : $account->tenant;

        if (
            !$profile ||
            strtolower((string) $profile->status) !== 'active'
        ) {
            $this->clearPendingLogin($request);
            return redirect()->route('login');
        }

        // Mark OTP as used. Only one request can consume it.
        $updated = DB::table('otp_verifications')
            ->where('otp_id', $otpId)
            ->where('user_id', $accountId)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['verified_at' => now()]);

        if ($updated !== 1) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')
                ->withErrors(['otp' => 'OTP is no longer valid.']);
        }

        $this->clearPendingLogin($request);

        // Authentication happens ONLY after successful OTP.
        Auth::login($account);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    // Generate another code with a cooldown.
    public function resendOtp(Request $request)
    {
        if (!$this->hasValidPendingLogin($request)) {
            $this->clearPendingLogin($request);
            return redirect()->route('login');
        }

        $resendAfter = (int) $request->session()
            ->get('pending_2fa_resend_after', 0);

        if (now()->timestamp < $resendAfter) {
            return back()->withErrors([
                'otp' => 'Please wait 60 seconds before requesting another code.',
            ]);
        }

        $account = Account::find(
            $request->session()->get('pending_2fa_account_id')
        );

        if (!$account || !filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
            $this->clearPendingLogin($request);
            return redirect()->route('login');
        }

        $otpId = $this->issueOtp($account);

        $request->session()->put([
            'pending_2fa_otp_id' => $otpId,
            'pending_2fa_expires_at' => now()->addMinutes(5)->timestamp,
            'pending_2fa_attempts' => 0,
            'pending_2fa_resend_after' => now()->addSeconds(60)->timestamp,
        ]);

        return back()->with('status', 'New verification code generated.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
