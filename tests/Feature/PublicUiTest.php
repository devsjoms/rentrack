<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicUiTest extends TestCase
{
    public function test_login_uses_the_required_label_and_security_links(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('>Login<', false)
            ->assertDontSee('Continue to verification')
            ->assertSee(route('password.otp.request'), false)
            ->assertSee(route('register'), false);
    }

    public function test_landing_page_exposes_only_public_navigation(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Smarter rental management starts here.')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertDontSee('Admin portal');
    }
}
