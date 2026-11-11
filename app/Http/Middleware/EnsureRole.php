<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {

        $account = $request->user();

        // Only the required role can proceed.
        if (!$account || $account->role !== $role) {
            abort(403, 'Access denied. Unauthorized role.');
        }

        // Verify linked profile is still active.
        $profile = $role === 'admin'
            ? $account->admin
            : $account->tenant;

        if (
            !$profile ||
            strtolower((string) $profile->status) !== 'active'
        ) {
            abort(403, 'Account profile unavailable or inactive.');
        }

        return $next($request);
    }
}
