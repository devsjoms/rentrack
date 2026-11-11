<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    private const OTP_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function showRequestForm(): View
    {
        return view('auth.password-otp.request');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $account = Account::query()->where('email', $email)->first();
        $attemptKey = 'rentrack-password-reset-attempt:' . hash('sha256', $email);

        if ($account) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($email)->send(
                    new PasswordResetOtpMail($code, self::OTP_MINUTES)
                );
            } catch (Throwable $exception) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

                report($exception);
            }
        }

        // A newly issued code starts a fresh, independent verification window.
        RateLimiter::clear($attemptKey);

        return redirect()->route('password.otp.form', ['email' => $email])
            ->with('status', 'If an account matches that email, a reset code has been sent.');
    }

    public function showResetForm(Request $request): View
    {
        return view('auth.password-otp.reset', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $attemptKey = 'rentrack-password-reset-attempt:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($attemptKey, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'otp' => 'Too many attempts. Request a new reset code.',
            ]);
        }

        $completed = DB::transaction(function () use ($email, $data): bool {
            // Locking the token makes a reset code single-use even for
            // simultaneous requests.
            $reset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (
                !$reset
                || !$reset->created_at
                || now()->diffInMinutes($reset->created_at, false) < -self::OTP_MINUTES
                || !Hash::check($data['otp'], $reset->token)
            ) {
                return false;
            }

            $account = Account::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (!$account) {
                return false;
            }

            $updated = Account::query()
                ->whereKey($account->getKey())
                ->update(['password_hash' => Hash::make($data['password'])]);

            if ($updated !== 1) {
                abort(409, 'Password reset could not be completed.');
            }

            // A reset is a credential-security event. Existing authenticated
            // sessions must not remain usable with the previous password.
            DB::table('sessions')
                ->where('user_id', $account->getAuthIdentifier())
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            return true;
        });

        if (!$completed) {
            RateLimiter::hit($attemptKey, self::OTP_MINUTES * 60);

            return back()->withErrors([
                'otp' => 'The email address or reset code is invalid or expired.',
            ]);
        }

        RateLimiter::clear($attemptKey);

        return redirect()->route('login')->with(
            'status',
            'Password reset successfully. You may now log in.'
        );
    }
}
