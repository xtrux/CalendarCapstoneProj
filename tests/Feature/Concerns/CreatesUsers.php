<?php

namespace Tests\Feature\Concerns;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shared helpers for creating users and groups in feature tests.
 *
 * Uses Model::create() instead of factories, because the default UserFactory
 * fills a `name` column that your users table doesn't have.
 */
trait CreatesUsers
{
    protected const PASSWORD = 'Password123';

    protected function makeUser(array $overrides = []): User
    {
        $unique = Str::lower(Str::random(8));

        return User::create(array_merge([
            'username' => 'user'.$unique,
            'email' => 'user'.$unique.'@example.com',
            'password' => self::PASSWORD, // hashed by the model's 'hashed' cast
        ], $overrides));
    }

    protected function makeGroup(User $admin, array $overrides = []): Group
    {
        $group = Group::create(array_merge([
            'name' => 'Test Group',
            'admin_user_id' => $admin->id,
            'invite_code' => Str::random(8),
        ], $overrides));

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        return $group;
    }

    protected function addMember(Group $group, User $user): GroupMember
    {
        return GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }
}
