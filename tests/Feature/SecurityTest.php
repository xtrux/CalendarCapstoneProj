<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Week 9: SQL injection and XSS (NF1), encryption (NF4), error logging (NF5).
 * Test cases 23–25 in the design doc.
 *
 * The XSS tests check that a script never reaches the page *as HTML*. They
 * pass whether you escape on output (Blade's default) or strip tags on input.
 */
class SecurityTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ---- SQL injection (NF1) -----------------------------------------------

    public function test_sql_injection_in_login_email_is_rejected(): void
    {
        $this->makeUser();

        $this->from('/login')->post('/login', [
            'email' => "' OR '1'='1' --",
            'password' => 'anything',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sql_injection_in_login_password_does_not_log_in(): void
    {
        $user = $this->makeUser();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => "' OR '1'='1",
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertEquals(1, $user->fresh()->failed_login_attempts);
    }

    public function test_sql_in_a_username_is_stored_as_plain_text(): void
    {
        $sneaky = "robert'); DROP TABLE users;--";

        $this->post('/register', [
            'username' => $sneaky,
            'email' => 'robert@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect('/login');

        // The users table still exists and holds the text exactly as typed.
        $this->assertSame(1, User::count());
        $this->assertSame($sneaky, User::first()->username);
    }

    public function test_sql_in_calendar_parameters_is_harmless(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);
        $url = '/group/'.$group->id.'/calendar';

        // users[] values are cast to integers, so this becomes a plain number.
        $this->actingAs($admin)
            ->get($url.'?'.http_build_query(['users' => ['1 OR 1=1']]))
            ->assertOk();

        // date must match YYYY-MM-DD exactly.
        $this->actingAs($admin)->from($url)
            ->get($url.'?'.http_build_query(['date' => "2026-01-01' OR '1'='1"]))
            ->assertSessionHasErrors('date');
    }

    // ---- Cross-site scripting (NF1, test cases 23–24) ----------------------

    public function test_script_in_a_group_name_is_not_rendered_as_html(): void
    {
        $admin = $this->makeUser();
        $script = '<script>alert("xss")</script>';

        $this->actingAs($admin)->post('/group/create', ['name' => $script]);
        $group = Group::first();

        // Group page heading and the sidebar both print the name.
        $this->actingAs($admin)->get('/group/'.$group->id)
            ->assertOk()
            ->assertDontSee($script, false);
    }

    public function test_script_in_a_username_is_not_rendered_as_html(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);
        $attacker = $this->makeUser(['username' => '<img src=x onerror=alert(1)>']);
        $this->addMember($group, $attacker);

        $this->actingAs($admin)->get('/group/'.$group->id)
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    // ---- Error logging (NF5, test case 25) ---------------------------------

    public function test_server_errors_are_written_to_the_log(): void
    {
        Route::get('/_test-error', fn () => throw new RuntimeException('Logging test failure'));
        Log::spy();

        $this->get('/_test-error')->assertStatus(500);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains((string) $message, 'Logging test failure'));
    }

    // ---- Encryption (NF4) --------------------------------------------------

    // Needs SESSION_ENCRYPT=true in your .env (and on the droplet).
    public function test_session_data_is_encrypted(): void
    {
        $this->assertTrue(config('session.encrypt'), 'Set SESSION_ENCRYPT=true in .env');
    }

    public function test_passwords_are_hashed_not_stored_as_typed(): void
    {
        $user = $this->makeUser();

        $this->assertNotSame(self::PASSWORD, $user->fresh()->password);
        $this->assertStringStartsWith('$2y$', $user->fresh()->password); // bcrypt
    }
}
