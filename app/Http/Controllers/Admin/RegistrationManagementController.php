<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Mail\RegistrationInvitationMail;
use App\Models\Account;
use App\Models\RegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RegistrationManagementController extends Controller
{
    public function index(): View
    {
        $requests = RegistrationRequest::query()
            ->orderByRaw(
                "CASE WHEN status = 'Pending' THEN 0 ELSE 1 END"
            )
            ->orderByDesc('registration_id')
            ->paginate(10);

        return view('admin.index', [
            'requests' => $requests,
        ]);
    }


    public function approve(
        RegistrationRequest $registrationRequest
    ): RedirectResponse {

        if (Account::where('email', $registrationRequest->email)->exists()) {
            return back()->withErrors([
                'request' => 'This email already has an account.',
            ]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        // Atomic conditional update prevents double approval.
        $updated = RegistrationRequest::query()
            ->whereKey($registrationRequest->getKey())
            ->where('status', 'Pending')
            ->update([
                'status' => 'Approved',
                'token' => $tokenHash,
                'expires_at' => now()->addHours(72),
                'approved_at' => now(),
            ]);

        if ($updated !== 1) {
            return back()->withErrors([
                'request' => 'Only pending requests can be approved.',
            ]);
        }

        $invitationUrl = route('registration.complete', [
            'token' => $rawToken,
        ]);

        try {
            Mail::to($registrationRequest->email)->send(
                new RegistrationInvitationMail($invitationUrl)
            );
        } catch (Throwable $exception) {
            report($exception);

            // Restore the request if sending fails.
            RegistrationRequest::query()
                ->whereKey($registrationRequest->getKey())
                ->where('status', 'Approved')
                ->where('token', $tokenHash)
                ->update([
                    'status' => 'Pending',
                    'token' => null,
                    'expires_at' => null,
                    'approved_at' => null,
                ]);

            return back()->withErrors([
                'request' => 'Invitation could not be sent. Please retry.',
            ]);
        }

        return back()->with(
            'status',
            'Registration approved. Invitation sent.'
        );
    }

    public function reject(
        RegistrationRequest $registrationRequest
    ): RedirectResponse {

        $updated = RegistrationRequest::query()
            ->whereKey($registrationRequest->getKey())
            ->where('status', 'Pending')
            ->update([
                'status' => 'Rejected',
                'token' => null,
                'expires_at' => null,
                'approved_at' => null,
            ]);

        if ($updated !== 1) {
            return back()->withErrors([
                'request' => 'Only pending requests can be rejected.',
            ]);
        }

        return back()->with(
            'status',
            'Registration request rejected.'
        );
    }
    

public function resend(
    RegistrationRequest $registrationRequest
): RedirectResponse {
    $registrationRequest->refresh();

    $expired = $registrationRequest->status === 'Expired'
        || (
            $registrationRequest->status === 'Approved'
            && $registrationRequest->expires_at !== null
            && \Illuminate\Support\Carbon::parse(
                $registrationRequest->expires_at
            )->lessThanOrEqualTo(now())
        );

    if (!$expired) {
        return back()->withErrors([
            'request' => 'Only expired invitations can be resent.',
        ]);
    }

    if (Account::where(
        'email',
        $registrationRequest->email
    )->exists()) {
        return back()->withErrors([
            'request' => 'An account already exists for this email.',
        ]);
    }

    $oldStatus = $registrationRequest->status;
    $oldToken = $registrationRequest->token;
    $oldExpiresAt = $registrationRequest->expires_at;
    $oldApprovedAt = $registrationRequest->approved_at;

    // Generate a fresh secure registration token.
    $rawToken = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);

    // Update only if the request is still eligible.
    $updated = RegistrationRequest::query()
        ->whereKey($registrationRequest->getKey())
        ->where(function ($query) {
            $query->where('status', 'Expired')
                ->orWhere(function ($query) {
                    $query->where('status', 'Approved')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
        })
        ->update([
            'status' => 'Approved',
            'token' => $hashedToken,
            'expires_at' => now()->addHours(72),
            'approved_at' => now(),
        ]);

    if ($updated !== 1) {
        return back()->withErrors([
            'request' => 'Request changed. Please refresh and try again.',
        ]);
    }

    $url = route('registration.complete', [
        'token' => $rawToken,
    ]);

    try {
        Mail::to($registrationRequest->email)->send(
            new RegistrationInvitationMail($url)
        );
    } catch (Throwable $exception) {
        report($exception);

        // Restore previous state only if our token
        // is still the latest token in the database.
        RegistrationRequest::query()
            ->whereKey($registrationRequest->getKey())
            ->where('token', $hashedToken)
            ->where('status', 'Approved')
            ->update([
                'status' => $oldStatus,
                'token' => $oldToken,
                'expires_at' => $oldExpiresAt,
                'approved_at' => $oldApprovedAt,
            ]);

        return back()->withErrors([
            'request' => 'Invitation email could not be sent.',
        ]);
    }

    return back()->with(
        'status',
        'A new registration link has been generated and sent.'
    );
}
}
