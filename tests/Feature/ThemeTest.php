<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Week 8: dark mode toggle and saved theme preference (NF7).
 */
class ThemeTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_user_can_switch_to_dark_mode(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')
            ->post('/changeTheme', ['theme' => 'dark'])
            ->assertRedirect('/settings');

        $this->assertSame('dark', $user->fresh()->theme_preference);
    }

    public function test_user_can_switch_back_to_light_mode(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['theme_preference' => 'dark'])->save();

        $this->actingAs($user)->from('/settings')
            ->post('/changeTheme', ['theme' => 'light']);

        $this->assertSame('light', $user->fresh()->theme_preference);
    }

    public function test_unknown_theme_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')
            ->post('/changeTheme', ['theme' => 'purple'])
            ->assertSessionHasErrors('theme');

        $this->assertSame('light', $user->fresh()->theme_preference);
    }

    public function test_pages_load_in_the_saved_theme(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['theme_preference' => 'dark'])->save();

        $this->actingAs($user)->get('/settings')
            ->assertOk()
            ->assertSee('data-bs-theme="dark"', false)
            ->assertSee('Light mode'); // the toggle offers the other theme
    }

    public function test_new_users_start_in_light_mode(): void
    {
        $user = $this->makeUser()->fresh();

        $this->assertSame('light', $user->theme_preference);

        $this->actingAs($user)->get('/settings')
            ->assertSee('data-bs-theme="light"', false)
            ->assertSee('Dark mode');
    }

    public function test_guests_see_light_mode(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-bs-theme="light"', false);
    }

    public function test_guests_cannot_change_the_theme(): void
    {
        $this->post('/changeTheme', ['theme' => 'dark'])
            ->assertRedirect('/login');
    }

    // Needs the JSON branch in SettingsController::changeTheme (the toggle's
    // fetch() asks for JSON so the page can switch without reloading).
    public function test_theme_toggle_answers_json(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->postJson('/changeTheme', ['theme' => 'dark'])
            ->assertOk()
            ->assertJson(['theme' => 'dark']);

        $this->assertSame('dark', $user->fresh()->theme_preference);
    }
}
