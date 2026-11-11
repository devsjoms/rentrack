<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthenticationFlowTest extends IsolatedMySqlTestCase
{
    public function test_login_issues_a_hashed_otp_without_authenticating_the_account(): void
    {
        Mail::fake();
        $account = $this->activeTenant();

        $this->post(route('login.submit'), [
            'username' => $account->username,
            'password' => 'ValidPassword!123',
        ])->assertRedirect(route('otp.form'));

        $otp = DB::table('otp_verifications')
            ->where('user_id', $account->user_id)
            ->latest('otp_id')
            ->first();

        $this->assertNotNull($otp);
        $this->assertNotSame('123456', $otp->otp_hash);
        $this->assertGuest();
        Mail::assertSent(LoginOtpMail::class);
    }

    public function test_password_reset_consumes_the_token_and_changes_the_custom_password_hash(): void
    {
        $account = $this->activeTenant();
        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => $account->email,
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $this->post(route('password.otp.reset'), [
            'email' => $account->email,
            'otp' => $code,
            'password' => 'NewPassword!123',
            'password_confirmation' => 'NewPassword!123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword!123', $account->fresh()->password_hash));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $account->email]);
    }
}
