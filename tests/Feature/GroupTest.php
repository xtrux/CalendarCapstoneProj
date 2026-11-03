<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Groups (F2 – F2.5).
 *
 * The leave / delete / regenerate / remove tests use the routes from the
 * group-buttons change:
 *   POST /group/leave, /group/delete, /group/regenerate-invite, /group/remove-user
 * If those routes aren't in web.php yet, those tests fail with a 404.
 */
class GroupTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ---- Create (F2.2, PHPUnit case 15) -----------------------------------

    public function test_user_can_create_a_group_and_becomes_its_admin(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/group/create', ['name' => 'groupTest']);

        $group = Group::where('name', 'groupTest')->first();
        $this->assertNotNull($group);
        $this->assertEquals($user->id, $group->admin_user_id);
        $this->assertSame(8, strlen($group->invite_code));
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        $response->assertRedirect('/group/'.$group->id);
    }

    public function test_group_name_is_required(): void
    {
        $this->actingAs($this->makeUser())->from('/')
            ->post('/group/create', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('groups', 0);
    }

    public function test_each_group_gets_a_different_invite_code(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/group/create', ['name' => 'One']);
        $this->actingAs($user)->post('/group/create', ['name' => 'Two']);

        $this->assertSame(2, Group::distinct()->count('invite_code'));
    }

    // ---- View --------------------------------------------------------------

    public function test_member_can_view_group_page(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin, ['name' => 'Weekend Crew']);

        $this->actingAs($admin)->get('/group/'.$group->id)
            ->assertOk()
            ->assertSee('Weekend Crew');
    }

    public function test_non_member_cannot_view_group_page(): void
    {
        $group = $this->makeGroup($this->makeUser());

        $this->actingAs($this->makeUser())->get('/group/'.$group->id)
            ->assertForbidden();
    }

    public function test_missing_group_returns_404(): void
    {
        $this->actingAs($this->makeUser())->get('/group/999999')
            ->assertNotFound();
    }

    // ---- Join (F2) ---------------------------------------------------------

    public function test_user_can_join_with_valid_invite_code(): void
    {
        $group = $this->makeGroup($this->makeUser());
        $joiner = $this->makeUser();

        $this->actingAs($joiner)
            ->post('/group/join', ['invite_code' => $group->invite_code])
            ->assertRedirect('/group/'.$group->id);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
            'role' => 'member',
        ]);
    }

    public function test_join_rejects_unknown_invite_code(): void
    {
        $this->makeGroup($this->makeUser(), ['invite_code' => 'AAAAAAAA']);
        $joiner = $this->makeUser();

        $this->actingAs($joiner)->from('/')
            ->post('/group/join', ['invite_code' => 'ZZZZZZZZ'])
            ->assertSessionHasErrors('invite_code');

        $this->assertDatabaseMissing('group_members', ['user_id' => $joiner->id]);
    }

    public function test_cannot_join_the_same_group_twice(): void
    {
        $group = $this->makeGroup($this->makeUser());
        $joiner = $this->makeUser();
        $this->addMember($group, $joiner);

        $this->actingAs($joiner)->from('/')
            ->post('/group/join', ['invite_code' => $group->invite_code])
            ->assertSessionHasErrors('invite_code');

        $this->assertSame(1, GroupMember::where('group_id', $group->id)
            ->where('user_id', $joiner->id)->count());
    }

    // ---- Leave (F2.1) ------------------------------------------------------

    public function test_member_can_leave_group(): void
    {
        $group = $this->makeGroup($this->makeUser());
        $member = $this->makeUser();
        $this->addMember($group, $member);

        $this->actingAs($member)
            ->post('/group/leave', ['group_id' => $group->id])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    // Requires the admin guard in leaveGroup ("Admins must delete the group
    // instead of leaving"). Without it, the admin leaves and this fails.
    public function test_admin_cannot_leave_their_own_group(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)->from('/group/'.$group->id)
            ->post('/group/leave', ['group_id' => $group->id])
            ->assertSessionHasErrors('failed');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $admin->id,
        ]);
    }

    // ---- Remove user (F2.4, PHPUnit case 16) ------------------------------

    public function test_admin_can_remove_a_member(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);
        $member = $this->makeUser();
        $this->addMember($group, $member);

        $this->actingAs($admin)
            ->post('/group/remove-user', ['group_id' => $group->id, 'removed_id' => $member->id])
            ->assertRedirect('/group/'.$group->id);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_non_admin_cannot_remove_a_member(): void
    {
        $group = $this->makeGroup($this->makeUser());
        $memberA = $this->makeUser();
        $memberB = $this->makeUser();
        $this->addMember($group, $memberA);
        $this->addMember($group, $memberB);

        $this->actingAs($memberA)->from('/group/'.$group->id)
            ->post('/group/remove-user', ['group_id' => $group->id, 'removed_id' => $memberB->id])
            ->assertSessionHasErrors('failed');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $memberB->id,
        ]);
    }

    // ---- Delete group (F2.3) -----------------------------------------------

    public function test_admin_can_delete_group_and_memberships_go_with_it(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);
        $this->addMember($group, $this->makeUser());

        $this->actingAs($admin)
            ->post('/group/delete', ['group_id' => $group->id])
            ->assertRedirect('/');

        $this->assertModelMissing($group);
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id]);
    }

    public function test_non_admin_cannot_delete_group(): void
    {
        $group = $this->makeGroup($this->makeUser());
        $member = $this->makeUser();
        $this->addMember($group, $member);

        $this->actingAs($member)->from('/group/'.$group->id)
            ->post('/group/delete', ['group_id' => $group->id])
            ->assertSessionHasErrors('failed');

        $this->assertModelExists($group);
    }

    // ---- Regenerate invite (F2.5) ------------------------------------------

    public function test_admin_can_regenerate_invite_code(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin, ['invite_code' => 'OLDCODE1']);

        $this->actingAs($admin)
            ->post('/group/regenerate-invite', ['group_id' => $group->id])
            ->assertSessionHasNoErrors();

        $newCode = $group->fresh()->invite_code;
        $this->assertNotSame('OLDCODE1', $newCode);
        $this->assertSame(8, strlen($newCode));
    }

    public function test_non_admin_cannot_regenerate_invite_code(): void
    {
        $group = $this->makeGroup($this->makeUser(), ['invite_code' => 'OLDCODE1']);
        $member = $this->makeUser();
        $this->addMember($group, $member);

        $this->actingAs($member)->from('/group/'.$group->id)
            ->post('/group/regenerate-invite', ['group_id' => $group->id])
            ->assertSessionHasErrors('failed');

        $this->assertSame('OLDCODE1', $group->fresh()->invite_code);
    }
}
