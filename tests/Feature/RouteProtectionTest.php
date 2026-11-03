<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guests must be sent to /login for every page that needs an account.
 */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedGetRoutes(): array
    {
        return [
            'settings' => ['/settings'],
            'group page' => ['/group/1'],
            'group calendar' => ['/group/1/calendar'],
        ];
    }

    #[DataProvider('protectedGetRoutes')]
    public function test_guests_are_redirected_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public static function protectedPostRoutes(): array
    {
        return [
            'create group' => ['/group/create'],
            'join group' => ['/group/join'],
            'change username' => ['/changeUsername'],
            'delete account' => ['/deleteUser'],
        ];
    }

    #[DataProvider('protectedPostRoutes')]
    public function test_guests_cannot_post_to_protected_routes(string $uri): void
    {
        $this->post($uri, [])->assertRedirect('/login');
    }
}
