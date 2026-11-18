<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Week 6: marking yourself busy on a day (F4) and the day colours (F4.4).
 */
class AvailabilityTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private User $admin;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->admin = $this->makeUser();
        $this->group = $this->makeGroup($this->admin);
    }

    private function toggleUrl(?Group $group = null): string
    {
        return '/group/'.($group ?? $this->group)->id.'/availability';
    }

    private function calendarUrl(int $year = 2026, int $month = 11, ?Group $group = null): string
    {
        return '/group/'.($group ?? $this->group)->id."/calendar?year=$year&month=$month";
    }

    // whereDate works on both SQLite (tests) and PostgreSQL (the droplet).
    private function busyRows(User $user, string $date, ?Group $group = null): int
    {
        return Availability::where('group_id', ($group ?? $this->group)->id)
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->count();
    }

    private function markBusy(User $user, string $date): void
    {
        Availability::create([
            'group_id' => $this->group->id,
            'user_id' => $user->id,
            'date' => $date,
            'status' => 'busy',
        ]);
    }

    private static function cellFor(array $cells, string $date): array
    {
        foreach ($cells as $cell) {
            if ($cell['date']->format('Y-m-d') === $date) {
                return $cell;
            }
        }

        self::fail("No calendar cell for $date");
    }

    // ---- Marking availability (F4) -----------------------------------------

    public function test_clicking_a_free_day_marks_you_busy(): void
    {
        $this->actingAs($this->admin)
            ->post($this->toggleUrl(), ['date' => '2026-11-10'])
            ->assertRedirect($this->calendarUrl());

        $this->assertSame(1, $this->busyRows($this->admin, '2026-11-10'));
    }

    public function test_clicking_a_busy_day_marks_you_free_again(): void
    {
        $this->markBusy($this->admin, '2026-11-10');

        $this->actingAs($this->admin)
            ->post($this->toggleUrl(), ['date' => '2026-11-10']);

        $this->assertSame(0, $this->busyRows($this->admin, '2026-11-10'));
    }

    public function test_clicking_twice_never_creates_duplicates(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->admin)->post($this->toggleUrl(), ['date' => '2026-11-10']);
        }

        // busy, free, busy
        $this->assertSame(1, $this->busyRows($this->admin, '2026-11-10'));
    }

    public function test_your_click_does_not_change_other_members(): void
    {
        $alice = $this->makeUser();
        $this->addMember($this->group, $alice);
        $this->markBusy($alice, '2026-11-10');

        $this->actingAs($this->admin)->post($this->toggleUrl(), ['date' => '2026-11-10']);
        $this->actingAs($this->admin)->post($this->toggleUrl(), ['date' => '2026-11-10']);

        $this->assertSame(1, $this->busyRows($alice, '2026-11-10'));
    }

    // Persistence: the mark is still there when the calendar is loaded again.
    public function test_busy_day_is_still_marked_after_reloading_the_calendar(): void
    {
        $this->actingAs($this->admin)->post($this->toggleUrl(), ['date' => '2026-11-10']);

        $this->actingAs($this->admin)
            ->get($this->calendarUrl())
            ->assertOk()
            ->assertSee("You're busy")
            ->assertViewHas('cells', function (array $cells) {
                $cell = self::cellFor($cells, '2026-11-10');

                return $cell['meBusy'] === true && $cell['busyCount'] === 1;
            });
    }

    public function test_availability_is_separate_for_each_group(): void
    {
        $otherGroup = $this->makeGroup($this->admin, ['name' => 'Other Group']);

        $this->actingAs($this->admin)->post($this->toggleUrl(), ['date' => '2026-11-10']);

        $this->assertSame(0, $this->busyRows($this->admin, '2026-11-10', $otherGroup));

        $this->actingAs($this->admin)
            ->get($this->calendarUrl(group: $otherGroup))
            ->assertViewHas('cells', fn (array $cells) => self::cellFor($cells, '2026-11-10')['busyCount'] === 0);
    }

    // ---- The no-refresh click (JSON) ---------------------------------------

    public function test_click_from_the_page_returns_the_new_state_as_json(): void
    {
        $this->actingAs($this->admin)
            ->postJson($this->toggleUrl(), ['date' => '2026-11-10'])
            ->assertOk()
            ->assertExactJson([
                'busy' => true,
                'busyCount' => 1,
                'memberCount' => 1,
                'status' => 'allBusy',
            ]);

        $this->actingAs($this->admin)
            ->postJson($this->toggleUrl(), ['date' => '2026-11-10'])
            ->assertOk()
            ->assertJson(['busy' => false, 'busyCount' => 0, 'status' => 'allFree']);
    }

    // ---- Colours on the calendar (F4.4, test cases 17–19) ------------------

    public function test_calendar_colours_days_by_how_many_members_are_busy(): void
    {
        $members = [$this->admin];
        for ($i = 0; $i < 3; $i++) {
            $member = $this->makeUser();
            $this->addMember($this->group, $member);
            $members[] = $member;
        }

        // Nov 11: 2 of 4 busy. Nov 12: all 4 busy. Nov 10: nobody.
        $this->markBusy($members[0], '2026-11-11');
        $this->markBusy($members[1], '2026-11-11');
        foreach ($members as $member) {
            $this->markBusy($member, '2026-11-12');
        }

        $this->actingAs($this->admin)
            ->get($this->calendarUrl())
            ->assertOk()
            ->assertViewHas('cells', function (array $cells) {
                return self::cellFor($cells, '2026-11-10')['status'] === 'allFree'
                    && self::cellFor($cells, '2026-11-11')['status'] === 'half'
                    && self::cellFor($cells, '2026-11-12')['status'] === 'allBusy'
                    && self::cellFor($cells, '2026-11-11')['busyCount'] === 2;
            });
    }

    // ---- Who can mark and what's accepted ----------------------------------

    public function test_non_member_cannot_mark_availability(): void
    {
        $outsider = $this->makeUser();

        $this->actingAs($outsider)
            ->post($this->toggleUrl(), ['date' => '2026-11-10'])
            ->assertForbidden();

        $this->assertSame(0, Availability::count());
    }

    public function test_guest_cannot_mark_availability(): void
    {
        $this->post($this->toggleUrl(), ['date' => '2026-11-10'])
            ->assertRedirect('/login');

        $this->assertSame(0, Availability::count());
    }

    public function test_bad_dates_are_rejected(): void
    {
        foreach (['', 'not-a-date', '2026-02-31', '11/10/2026'] as $bad) {
            $this->actingAs($this->admin)->from($this->calendarUrl())
                ->post($this->toggleUrl(), ['date' => $bad])
                ->assertSessionHasErrors('date');
        }

        $this->assertSame(0, Availability::count());
    }
}
