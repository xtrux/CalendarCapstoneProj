<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountLocked;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Forgot password (F1.8, PHPUnit case 14) and email unlock after lockout (NF2).
 * Uses the CreatesUsers helper from tests/Feature/Concerns.
 */
class PasswordResetAndUnlockTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function lock(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addHour(),
        ])->save();
    }

    // ---- Forgot password ---------------------------------------------------

    public function test_forgot_password_page_loads(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Reset your password');
    }

    public function test_reset_link_is_emailed(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_same_message_and_no_email(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_reset_page_loads_with_email_filled_in(): void
    {
        $this->get('/reset-password/some-token?email=test@test.com')
            ->assertOk()
            ->assertSee('test@test.com');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('BrandNew123', $user->fresh()->password));
    }

    public function test_resetting_password_also_unlocks_account(): void
    {
        $user = $this->makeUser();
        $this->lock($user);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ]);

        $this->assertNull($user->fresh()->locked_until);
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    public function test_reset_rejects_invalid_token(): void
    {
        $user = $this->makeUser();

        $this->from('/reset-password/bad-token')->post('/reset-password', [
            'token' => 'bad-token',
            'email' => $user->email,
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_reset_enforces_password_rules(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouppercase1',
            'password_confirmation' => 'nouppercase1',
        ])->assertSessionHasErrors('password');
    }

    // ---- Lockout and email unlock ------------------------------------------

    public function test_locking_the_account_sends_one_unlock_email(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'WrongPassword1',
            ]);
        }

        Notification::assertSentToTimes($user, AccountLocked::class, 1);
        $this->assertTrue($user->fresh()->locked_until->isFuture());
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['failed_login_attempts' => 3])->save();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);

        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    public function test_unlock_link_clears_the_lock(): void
    {
        $user = $this->makeUser();
        $this->lock($user);
        $url = URL::temporarySignedRoute('account.unlock', now()->addMinutes(60), ['user' => $user->id]);

        $this->get($url)->assertRedirect('/login');

        $this->assertNull($user->fresh()->locked_until);
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unsigned_unlock_link_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->lock($user);

        $this->get('/unlock/'.$user->id)->assertForbidden();

        $this->assertNotNull($user->fresh()->locked_until);
    }

    public function test_expired_unlock_link_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->lock($user);
        $url = URL::temporarySignedRoute('account.unlock', now()->addMinutes(60), ['user' => $user->id]);

        $this->travel(61)->minutes();

        $this->get($url)->assertForbidden();
    }
}
