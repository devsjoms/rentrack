<?php

namespace Tests\Feature;

use App\Mail\RegistrationInvitationMail;
use App\Models\RegistrationRequest;
use Illuminate\Support\Facades\Mail;

class InvitationResendAuthorizationTest extends IsolatedMySqlTestCase
{
    public function test_a_guest_cannot_resend_an_invitation(): void
    {
        $request = RegistrationRequest::factory()->create();

        $this->post(route('admin.registrations.resend', $request))
            ->assertRedirect(route('login'));
    }

    public function test_a_tenant_cannot_resend_an_invitation(): void
    {
        $request = RegistrationRequest::factory()->create();

        $this->actingAs($this->activeTenant())
            ->post(route('admin.registrations.resend', $request))
            ->assertForbidden();
    }

    public function test_an_active_admin_can_resend_only_an_expired_invitation(): void
    {
        Mail::fake();
        $request = RegistrationRequest::factory()->create([
            'status' => 'Expired',
            'token' => hash('sha256', 'old-token'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->activeAdmin())
            ->post(route('admin.registrations.resend', $request))
            ->assertSessionHas('status');

        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertNotSame(hash('sha256', 'old-token'), $request->token);
        Mail::assertSent(RegistrationInvitationMail::class);
    }
}
