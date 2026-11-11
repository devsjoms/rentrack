<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomController extends Controller
{
    // READ: Display active and archived rooms.
    public function index(): View
    {
        $rooms = Room::query()
            ->where('availability_status', '!=', 'Archived')
            ->withCount(['tenants as occupied_count'])
            ->orderBy('building_number')
            ->orderBy('room_number')
            ->get();

        $archivedRooms = Room::query()
            ->where('availability_status', 'Archived')
            ->orderBy('building_number')
            ->orderBy('room_number')
            ->get();

        return view('admin.rooms.index', compact('rooms', 'archivedRooms'));
    }

    // CREATE: Add a new room.
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'building_number' => ['required', 'string', 'max:20'],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'room_number')
                    ->where('building_number', $request->building_number),
            ],
            'building_address' => ['required', 'string', 'max:150'],
            'gender_allowed' => ['required', Rule::in(['Male', 'Female'])],
            'maximum_tenants' => ['required', 'integer', 'min:1', 'max:100'],
            'rent_price' => [
                'required', 'numeric', 'min:0',
                'max:99999999.99', 'decimal:0,2',
            ],
        ]);

        Room::create([
            ...$data,
            'tenants_count' => 0,
            'availability_status' => 'Available',
        ]);

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room created successfully!');
    }

    // EDIT: Display the edit form.
    public function edit(Room $room): View
    {
        abort_if($room->availability_status === 'Archived', 404);
        $room->loadCount('tenants');

        return view('admin.rooms.edit', compact('room'));
    }

    // UPDATE: Validate and save edits atomically with room-level locking.
    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'building_number' => ['required', 'string', 'max:20'],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'room_number')
                    ->where('building_number', $request->building_number)
                    ->ignore($room->room_id, 'room_id'),
            ],
            'building_address' => ['required', 'string', 'max:150'],
            'gender_allowed' => ['required', Rule::in(['Male', 'Female'])],
            'maximum_tenants' => ['required', 'integer', 'min:1', 'max:100'],
            'rent_price' => [
                'required', 'numeric', 'min:0',
                'max:99999999.99', 'decimal:0,2',
            ],
            'availability_status' => [
                'required', Rule::in(['Available', 'Unavailable']),
            ],
        ]);

        DB::transaction(function () use ($room, $data) {
            // Use the same room-row lock as request approval and archiving.
            $lockedRoom = Room::query()
                ->whereKey($room->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->availability_status === 'Archived') {
                throw ValidationException::withMessages([
                    'room' => 'Restore this room before editing.',
                ]);
            }

            // Calculate actual assignments after acquiring the room lock.
            $occupied = $lockedRoom->tenants()->count();

            if ((int) $data['maximum_tenants'] < $occupied) {
                throw ValidationException::withMessages([
                    'maximum_tenants' =>
                        "This room has {$occupied} assigned tenants. "
                        . 'Capacity cannot be lower than occupancy.',
                ]);
            }

            if (
                $occupied > 0
                && $lockedRoom->gender_allowed !== $data['gender_allowed']
            ) {
                throw ValidationException::withMessages([
                    'gender_allowed' =>
                        'Cannot change gender restriction while tenants are assigned.',
                ]);
            }

            // Keep occupancy consistent; the admin cannot edit it directly.
            $lockedRoom->update([
                ...$data,
                'tenants_count' => $occupied,
            ]);
        }, 5);

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room updated successfully!');
    }

    // DELETE: Only delete rooms with no linked tenants, requests, or billings.
    // There is no active destroy route unless one is added separately.
    public function destroy(Room $room): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($room) {
            $lockedRoom = Room::query()
                ->whereKey($room->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $roomId = $lockedRoom->room_id;

            $hasTenants = $lockedRoom->tenants()->exists();
            $hasRequests = DB::table('room_requests')
                ->where('room_id', $roomId)
                ->exists();
            $hasBillings = DB::table('billings')
                ->where('room_id', $roomId)
                ->exists();

            if ($hasTenants || $hasRequests || $hasBillings) {
                return false;
            }

            $lockedRoom->delete();
            return true;
        });

        if (!$deleted) {
            return back()->withErrors([
                'room' =>
                    'Cannot delete this room because it has tenants, '
                    . 'room requests, or billing history. '
                    . 'Set its availability to Unavailable instead.',
            ]);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room deleted successfully!');
    }

    // ARCHIVE: Preserve history; do not archive rooms with tenants or pending requests.
    public function archive(Room $room): RedirectResponse
    {
        $result = DB::transaction(function () use ($room) {
            $lockedRoom = Room::query()
                ->whereKey($room->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->availability_status === 'Archived') {
                return 'already_archived';
            }

            if ($lockedRoom->tenants()->exists()) {
                return 'occupied';
            }

            $hasPendingRequests = DB::table('room_requests')
                ->where('room_id', $lockedRoom->room_id)
                ->where('status', 'Pending')
                ->exists();

            if ($hasPendingRequests) {
                return 'pending_requests';
            }

            $lockedRoom->update(['availability_status' => 'Archived']);
            return 'success';
        });

        if ($result !== 'success') {
            $message = match ($result) {
                'occupied' => 'Cannot archive a room with assigned tenants.',
                'pending_requests' => 'Resolve pending room requests before archiving.',
                default => 'This room is already archived.',
            };

            return redirect()
                ->route('admin.rooms.index')
                ->withErrors(['room' => $message]);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room archived successfully!');
    }

    // RESTORE: Require manual activation after restoring.
    public function restore(Room $room): RedirectResponse
    {
        $restored = DB::transaction(function () use ($room) {
            $lockedRoom = Room::query()
                ->whereKey($room->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRoom->availability_status !== 'Archived') {
                return false;
            }

            $lockedRoom->update(['availability_status' => 'Unavailable']);
            return true;
        });

        if (!$restored) {
            return redirect()
                ->route('admin.rooms.index')
                ->withErrors(['room' => 'This room is not archived.']);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'Room restored successfully!');
    }
}