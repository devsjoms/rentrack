<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\RegistrationRequest;
use App\Models\Room;
use App\Models\RoomRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'tenant' => redirect()->route('tenant.dashboard'),
            default => abort(403, 'Invalid account role.'),
        };
    }

    public function admin(Request $request): View
    {
        return view('admin.dashboard', [
            'account' => $request->user(),
            'overview' => [
                'rooms' => Room::where('availability_status', '!=', 'Archived')->count(),
                'requests' => RoomRequest::where('status', 'Pending')->count(),
                'billings' => Billing::where('status', 'Unpaid')->count(),
                'registrations' => RegistrationRequest::where('status', 'Pending')->count(),
            ],
        ]);
    }

    public function tenant(Request $request): View
    {
        return view('tenant.dashboard', [
            'account' => $request->user(),
            'overview' => [
                'assigned' => $request->user()->tenant?->room_id !== null,
                'requests' => RoomRequest::where('tenant_id', $request->user()->tenant_id)->where('status', 'Pending')->count(),
                'billings' => Billing::where('tenant_id', $request->user()->tenant_id)->where('status', 'Unpaid')->count(),
            ],
        ]);
    }
}
