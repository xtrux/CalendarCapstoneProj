<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Account settings (F1.3 – F1.7).
 */
class SettingsTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_settings_page_loads_for_logged_in_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/settings')
            ->assertOk()
            ->assertSee($user->username);
    }

    // ---- Username (F1.7) --------------------------------------------------

    public function test_user_can_change_username(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/changeUsername', ['username' => 'testNew'])
            ->assertRedirect('/settings');

        $this->assertSame('testNew', $user->fresh()->username);
    }

    public function test_cannot_change_username_to_one_already_taken(): void
    {
        $this->makeUser(['username' => 'taken']);
        $user = $this->makeUser();
        $original = $user->username;

        $this->actingAs($user)->from('/settings')
            ->post('/changeUsername', ['username' => 'taken'])
            ->assertSessionHasErrors('username');

        $this->assertSame($original, $user->fresh()->username);
    }

    // ---- Email (F1.6) -----------------------------------------------------

    public function test_user_can_change_email(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/changeEmail', ['email' => 'test2@test.com'])
            ->assertRedirect('/settings');

        $this->assertSame('test2@test.com', $user->fresh()->email);
    }

    // Note: your test plan uses "test2@test" as the invalid example, but Laravel's
    // default 'email' rule accepts it (no TLD is required by the RFC). Use
    // 'email:filter' in SettingsController if you want that one rejected too.
    public function test_cannot_change_email_to_invalid_format(): void
    {
        $user = $this->makeUser();
        $original = $user->email;

        $this->actingAs($user)->from('/settings')
            ->post('/changeEmail', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_cannot_change_email_to_one_already_taken(): void
    {
        $this->makeUser(['email' => 'taken@example.com']);
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')
            ->post('/changeEmail', ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    }

    // ---- Password (F1.3) --------------------------------------------------

    public function test_user_can_change_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/changePassword', [
            'oldPass' => self::PASSWORD,
            'newPass' => 'NewPassword123',
            'newPass_confirmation' => 'NewPassword123',
        ])->assertRedirect('/settings');

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_old_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')->post('/changePassword', [
            'oldPass' => 'WrongPassword1',
            'newPass' => 'NewPassword123',
            'newPass_confirmation' => 'NewPassword123',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_change_password_enforces_password_rules(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')->post('/changePassword', [
            'oldPass' => self::PASSWORD,
            'newPass' => 'nouppercase1',
            'newPass_confirmation' => 'nouppercase1',
        ])->assertSessionHasErrors('newPass');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    // ---- Delete account (F1.4) -------------------------------------------

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/deleteUser', ['password' => self::PASSWORD])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertModelMissing($user);
    }

    public function test_delete_account_rejects_wrong_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')
            ->post('/deleteUser', ['password' => 'WrongPassword1'])
            ->assertSessionHasErrors('password');

        $this->assertModelExists($user);
        $this->assertAuthenticatedAs($user);
    }

    // ---- Profile picture (F1.5) -------------------------------------------
    // UploadedFile::fake()->image() needs PHP's GD extension.

    public function test_user_can_upload_profile_picture(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $this->actingAs($user)->post('/changeProfilePicture', [
            'profile_picture' => UploadedFile::fake()->image('avatar.png', 256, 256),
        ])->assertRedirect('/settings');

        $path = $user->fresh()->profile_picture;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('profile_pictures/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_new_profile_picture_replaces_old_file(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $this->actingAs($user)->post('/changeProfilePicture', [
            'profile_picture' => UploadedFile::fake()->image('first.png', 256, 256),
        ]);
        $firstPath = $user->fresh()->profile_picture;

        $this->actingAs($user)->post('/changeProfilePicture', [
            'profile_picture' => UploadedFile::fake()->image('second.png', 256, 256),
        ]);

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->fresh()->profile_picture);
    }

    public function test_profile_picture_must_be_an_image(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $this->actingAs($user)->from('/settings')->post('/changeProfilePicture', [
            'profile_picture' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('profile_picture');

        $this->assertNull($user->fresh()->profile_picture);
    }
}
