<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Tenant;
use App\Models\RegistrationRequest;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    // Display public registration request page.
    public function index(): View
    {
        return view('auth.register');
    }

    // Store registration request.
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email:rfc',
                'max:255',
            ],
        ]);

        // Normalize the submitted email.
        $email = mb_strtolower(trim($data['email']));

        // Do not create requests for existing accounts.
        $accountExists = Account::where('email', $email)->exists();

        if (!$accountExists) {
            // Prevent duplicate requests for the same email.
            RegistrationRequest::firstOrCreate(
                ['email' => $email],
                ['status' => 'Pending']
            );
        }

        // Generic message avoids revealing existing accounts.
        return back()->with(
            'status',
            'If your email is eligible, your registration request has been received. Please wait for administrator review.'
        );
    }
    

public function showCompletion(string $token)
{
    $registration = RegistrationRequest::query()
        ->where('token', hash('sha256', $token))
        ->where('status', 'Approved')
        ->where('expires_at', '>', now())
        ->first();

    if (
    !$registration ||
    Account::where('email', $registration->email)->exists()
) {
    return response()->view('errors.410', [], 410);
}
    return response()
        ->view('auth.complete-registration', [
            'registration' => $registration,
            'token' => $token,
        ])
        ->withHeaders([
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
}

public function completeRegistration(
    Request $request,
    string $token
): RedirectResponse {

    // STEP 1: Validate submitted tenant information.
    $data = $request->validate([
        'tenant_name' => [
            'required', 'string', 'max:50'
        ],
        'tenant_age' => [
            'required', 'integer', 'min:1', 'max:120'
        ],
        'tenant_gender' => [
            'required', Rule::in(['Male', 'Female', 'Other'])
        ],
        'tenant_address' => [
            'required', 'string', 'max:70'
        ],
        'tenant_number' => [
            'required', 'string', 'max:50'
        ],
        'username' => [
            'required',
            'alpha_dash',
            'min:3',
            'max:30',
            Rule::unique('accounts', 'username'),
        ],
        'password' => [
            'required',
            'confirmed',
            Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols(),
        ],
    ]);

    // STEP 2: Create the profile and account atomically.
    DB::transaction(function () use ($data, $token) {

        // Lock the invitation against simultaneous submissions.
        $registration = RegistrationRequest::query()
            ->where('token', hash('sha256', $token))
            ->lockForUpdate()
            ->first();

        // STEP 3: Validate the invitation again.
        if (
            !$registration ||
            $registration->status !== 'Approved' ||
            !$registration->expires_at ||
            $registration->expires_at->isPast()
        ) {
            abort(410, 'Registration invitation is invalid or expired.');
        }

        if (Account::where('email', $registration->email)->exists()) {
            abort(410, 'This invitation is no longer available.');
        }

        // STEP 4: Create the tenant profile.
        // The database automatically generates tenant_id.
        $tenant = Tenant::create([
            'tenant_name' => $data['tenant_name'],
            'tenant_age' => $data['tenant_age'],
            'tenant_gender' => $data['tenant_gender'],
            'tenant_address' => $data['tenant_address'],
            'tenant_number' => $data['tenant_number'],
            'room_id' => null,
            'move_in_date' => null,
            'status' => 'Active',
            'image' => null,
        ]);

        // STEP 5: Create the linked account.
        Account::create([
            'admin_id' => null,
            'tenant_id' => $tenant->tenant_id,
            'username' => $data['username'],
            'email' => $registration->email,
            'password_hash' => Hash::make($data['password']),
            'role' => 'tenant',
            'profile_completed' => true,
        ]);

        // STEP 6: Consume the one-time invitation.
        $registration->status = 'Used';
        $registration->token = null;
        $registration->save();

    }, attempts: 3);

    // STEP 7: Redirect to login.
    return redirect()
        ->route('login')
        ->with(
            'status',
            'Registration completed successfully. You may now log in.'
        );
}

}
