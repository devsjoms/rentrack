<?php

namespace Tests\Feature;

use App\Models\Room;

class AuthorizationBoundaryTest extends IsolatedMySqlTestCase
{
    public function test_a_tenant_cannot_open_an_admin_billing_page(): void
    {
        $this->actingAs($this->activeTenant())
            ->get(route('admin.billings.index'))
            ->assertForbidden();
    }

    public function test_an_inactive_admin_profile_cannot_open_admin_pages(): void
    {
        $account = $this->activeAdmin();
        $account->admin->update(['status' => 'Inactive']);

        $this->actingAs($account)
            ->get(route('admin.rooms.index'))
            ->assertForbidden();
    }

    public function test_a_tenant_cannot_submit_a_room_request_for_an_ineligible_room(): void
    {
        $tenant = $this->activeTenant();
        $room = Room::factory()->create(['gender_allowed' => 'Female']);

        $this->actingAs($tenant)
            ->post(route('tenant.room-requests.store'), ['room_id' => $room->room_id])
            ->assertSessionHasErrors('room_id');
    }
}
