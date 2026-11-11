<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomRequestController extends Controller
{
    /**
     * Display eligible rooms and the tenant's request history.
     */
    public function index(Request $request): View
    {
        $tenantId = $request->user()?->tenant_id;

        // A legacy tenant ID of 0 is valid if that tenant exists.
        abort_if(
            $tenantId === null,
            403,
            'Tenant profile is not linked to this account.'
        );

        $tenant = Tenant::query()->findOrFail($tenantId);

        $requests = RoomRequest::query()
            ->with('room')
            ->where('tenant_id', $tenant->tenant_id)
            ->orderByDesc('request_date')
            ->orderByDesc('request_id')
            ->get();

        $hasPendingRequest = $requests->contains('status', 'Pending');

        // Keep request history visible even if the tenant is ineligible.
        // Only show requestable rooms to active, unassigned tenants.
        $availableRooms = collect();

        if ($tenant->status === 'Active' && $tenant->room_id === null) {
            $availableRooms = Room::query()
                ->where('availability_status', 'Available')
                ->withCount(['tenants as occupied_count'])
                ->orderBy('building_number')
                ->orderBy('room_number')
                ->get()
                ->filter(function (Room $room) use ($tenant): bool {
                    $matchesGender = strcasecmp(
                        trim((string) $tenant->tenant_gender),
                        trim((string) $room->gender_allowed)
                    ) === 0;

                    return $matchesGender
                        && $room->occupied_count < $room->maximum_tenants;
                })
                ->values();
        }

        return view('tenant.rooms.index', compact(
            'tenant',
            'requests',
            'hasPendingRequest',
            'availableRooms'
        ));
    }

    /**
     * Submit one pending room request without assigning a room yet.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'room_id'),
            ],
        ]);

        $tenantId = $request->user()?->tenant_id;

        abort_if(
            $tenantId === null,
            403,
            'Tenant profile is not linked to this account.'
        );

        DB::transaction(function () use ($tenantId, $data): void {
            // Serialize request submission and admin approval per tenant.
            $tenant = Tenant::query()
                ->whereKey($tenantId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->status !== 'Active') {
                throw ValidationException::withMessages([
                    'room_id' => 'Only active tenants may request a room.',
                ]);
            }

            if ($tenant->room_id !== null) {
                throw ValidationException::withMessages([
                    'room_id' => 'You are already assigned to a room.',
                ]);
            }

            $hasPendingRequest = RoomRequest::query()
                ->where('tenant_id', $tenant->tenant_id)
                ->where('status', 'Pending')
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages([
                    'room_id' => 'You already have a pending room request.',
                ]);
            }

            // Match the admin approval locking order: tenant, then room.
            $room = Room::query()
                ->whereKey($data['room_id'])
                ->lockForUpdate()
                ->first();

            if (!$room || $room->availability_status !== 'Available') {
                throw ValidationException::withMessages([
                    'room_id' => 'This room is currently unavailable.',
                ]);
            }

            if (strcasecmp(
                trim((string) $tenant->tenant_gender),
                trim((string) $room->gender_allowed)
            ) !== 0) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room does not match your gender profile.',
                ]);
            }

            // Do not trust the denormalized tenants_count for eligibility.
            $occupied = $room->tenants()->count();

            if ($occupied >= $room->maximum_tenants) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room has reached maximum capacity.',
                ]);
            }

            // Approval and occupancy updates belong to the admin workflow.
            RoomRequest::create([
                'tenant_id' => $tenant->tenant_id,
                'room_id' => $room->room_id,
                'status' => 'Pending',
            ]);
        }, 5);

        return redirect()
            ->route('tenant.rooms.index')
            ->with(
                'status',
                'Room request submitted successfully! Please wait for Admin approval.'
            );
    }
}
