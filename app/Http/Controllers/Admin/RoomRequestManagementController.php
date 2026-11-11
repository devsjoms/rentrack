<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomRequestManagementController extends Controller
{
    public function index(): View
    {
        $pendingRequests = RoomRequest::with(['tenant', 'room'])
            ->where('status', 'Pending')
            ->orderBy('request_date')
            ->orderBy('request_id')
            ->get();

        $history = RoomRequest::with(['tenant', 'room'])
            ->whereIn('status', ['Approved', 'Rejected'])
            ->orderByDesc('request_date')
            ->orderByDesc('request_id')
            ->limit(50)
            ->get();

        return view('admin.room-requests.index', compact(
            'pendingRequests',
            'history'
        ));
    }

    public function approve(RoomRequest $roomRequest): RedirectResponse
    {
        DB::transaction(function () use ($roomRequest): void {
            // Always lock in this order: tenant -> request -> room.
            // The tenant lock serializes actions for the same applicant.
            $tenant = Tenant::query()
                ->whereKey($roomRequest->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRequest = RoomRequest::query()
                ->whereKey($roomRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'Pending') {
                throw ValidationException::withMessages([
                    'request' => 'This request has already been processed.',
                ]);
            }

            if ((string) $lockedRequest->tenant_id !== (string) $tenant->tenant_id) {
                throw ValidationException::withMessages([
                    'request' => 'Tenant information does not match.',
                ]);
            }

            if ($tenant->room_id !== null) {
                throw ValidationException::withMessages([
                    'request' => 'Tenant is already assigned to a room.',
                ]);
            }

            if ($tenant->status !== 'Active') {
                throw ValidationException::withMessages([
                    'request' => 'Only active tenants may be assigned.',
                ]);
            }

            // The room lock also serializes approvals for different tenants
            // competing for the final vacant slot.
            $room = Room::query()
                ->whereKey($lockedRequest->room_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->availability_status !== 'Available') {
                throw ValidationException::withMessages([
                    'request' => 'The selected room is unavailable.',
                ]);
            }

            if (
                strcasecmp(
                    trim((string) $tenant->tenant_gender),
                    trim((string) $room->gender_allowed)
                ) !== 0
            ) {
                throw ValidationException::withMessages([
                    'request' => 'Tenant does not meet room gender requirements.',
                ]);
            }

            $occupied = $room->tenants()->count();

            if ($occupied >= (int) $room->maximum_tenants) {
                throw ValidationException::withMessages([
                    'request' => 'Room has reached maximum capacity.',
                ]);
            }

            // Assign and approve atomically. A failure rolls back both.
            $tenant->room_id = $room->room_id;
            $tenant->save();

            $lockedRequest->status = 'Approved';
            $lockedRequest->save();

            // No other request from this tenant should stay actionable.
            // Preserve the history: change statuses instead of deleting rows.
            RoomRequest::query()
                ->where('tenant_id', $tenant->tenant_id)
                ->where('status', 'Pending')
                ->where('request_id', '!=', $lockedRequest->request_id)
                ->update(['status' => 'Rejected']);

            // Keep denormalized occupancy aligned with actual assignments.
            // 'Available' remains an admin-managed availability setting;
            // fullness is enforced by the occupancy check above.
            $room->tenants_count = $room->tenants()->count();
            $room->save();
        }, 5);

        return redirect()
            ->route('admin.room-requests.index')
            ->with('status', 'Request approved! Tenant assigned successfully. Other pending requests were rejected.');
    }

    public function reject(RoomRequest $roomRequest): RedirectResponse
    {
        DB::transaction(function () use ($roomRequest): void {
            // Follow the same locking order as approval and submission.
            Tenant::query()
                ->whereKey($roomRequest->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRequest = RoomRequest::query()
                ->whereKey($roomRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'Pending') {
                throw ValidationException::withMessages([
                    'request' => 'This request has already been processed.',
                ]);
            }

            $lockedRequest->status = 'Rejected';
            $lockedRequest->save();
        }, 5);

        return redirect()
            ->route('admin.room-requests.index')
            ->with('status', 'Room request rejected.');
    }
}
