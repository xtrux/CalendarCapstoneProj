<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Availability;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Week 7: filter availability by user and by date (F4.1), search a date with
 * auto page-turn (F4.3), and pagination keeping the filter.
 *
 * Group used in every test: admin "adminuser" plus members "alice" and "bob".
 */
class FilterAndSearchTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private User $admin;
    private User $alice;
    private User $bob;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->admin = $this->makeUser(['username' => 'adminuser']);
        $this->alice = $this->makeUser(['username' => 'alice']);
        $this->bob = $this->makeUser(['username' => 'bob']);

        $this->group = $this->makeGroup($this->admin);
        $this->addMember($this->group, $this->alice);
        $this->addMember($this->group, $this->bob);
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

    private function calendarUrl(array $query = []): string
    {
        $url = '/group/'.$this->group->id.'/calendar';

        return $query ? $url.'?'.http_build_query($query) : $url;
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

    // ---- Filter by user (F4.1) ---------------------------------------------

    public function test_without_a_filter_everyone_is_counted(): void
    {
        $this->markBusy($this->alice, '2026-11-10');

        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11]))
            ->assertOk()
            ->assertViewHas('cells', function (array $cells) {
                $cell = self::cellFor($cells, '2026-11-10');

                return $cell['busyCount'] === 1
                    && $cell['status'] === CalendarController::statusFor(1, 3);
            });
    }

    public function test_filter_counts_only_the_selected_users(): void
    {
        $this->markBusy($this->alice, '2026-11-10');

        // Only Bob selected: Alice's busy day doesn't count, so the day is all free.
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11, 'users' => [$this->bob->id]]))
            ->assertViewHas('cells', function (array $cells) {
                $cell = self::cellFor($cells, '2026-11-10');

                return $cell['busyCount'] === 0
                    && $cell['status'] === CalendarController::statusFor(0, 1);
            });

        // Only Alice selected: the day is all busy.
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11, 'users' => [$this->alice->id]]))
            ->assertViewHas('selectedIds', fn ($ids) => $ids->values()->all() == [$this->alice->id])
            ->assertViewHas('cells', function (array $cells) {
                $cell = self::cellFor($cells, '2026-11-10');

                return $cell['busyCount'] === 1
                    && $cell['status'] === CalendarController::statusFor(1, 1);
            });
    }

    public function test_filter_ignores_users_outside_the_group(): void
    {
        $outsider = $this->makeUser();

        // An ID that isn't a member is dropped, which leaves no filter: everyone counts.
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['users' => [$outsider->id]]))
            ->assertOk()
            ->assertViewHas('selectedIds', fn ($ids) => $ids->count() === 3 && ! $ids->contains($outsider->id));
    }

    // Needs fix: remove ->whereIn('user_id', $selectedIds) from the query in display().
    public function test_your_busy_label_survives_filtering_yourself_out(): void
    {
        $this->markBusy($this->admin, '2026-11-10');

        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11, 'users' => [$this->alice->id]]))
            ->assertViewHas('cells', fn (array $cells) => self::cellFor($cells, '2026-11-10')['meBusy'] === true);
    }

    public function test_toggling_while_filtered_returns_filtered_counts(): void
    {
        $this->markBusy($this->alice, '2026-11-10');

        $this->actingAs($this->admin)
            ->postJson('/group/'.$this->group->id.'/availability', [
                'date' => '2026-11-10',
                'users' => [$this->alice->id, $this->admin->id],
            ])
            ->assertOk()
            ->assertJson([
                'busy' => true,
                'busyCount' => 2,
                'memberCount' => 2,
                'status' => CalendarController::statusFor(2, 2),
            ]);
    }

    // ---- Pagination keeps the filter (F4.2 + F4.1) ---------------------------

    public function test_prev_and_next_links_keep_the_user_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11, 'users' => [$this->alice->id]]))
            ->assertOk()
            ->assertSee('users%5B0%5D='.$this->alice->id, false);
    }

    // ---- Search a date / filter by date (F4.1, F4.3) ------------------------

    public function test_date_search_jumps_to_that_month_and_marks_the_day(): void
    {
        // Year/month say November 2026, but the searched date wins.
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['year' => 2026, 'month' => 11, 'date' => '2027-03-15']))
            ->assertOk()
            ->assertSee('March 2027')
            ->assertViewHas('cells', fn (array $cells) => self::cellFor($cells, '2027-03-15')['selected'] === true);
    }

    public function test_day_panel_lists_who_is_free_and_busy(): void
    {
        $this->markBusy($this->alice, '2026-11-20');

        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['date' => '2026-11-20']))
            ->assertOk()
            ->assertViewHas('dayDetails', function ($details) {
                $byName = collect($details)->keyBy('username');

                return $byName->count() === 3
                    && $byName['alice']['busy'] === true
                    && $byName['bob']['busy'] === false
                    && $byName['adminuser']['busy'] === false;
            });
    }

    public function test_day_panel_respects_the_user_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->calendarUrl(['date' => '2026-11-20', 'users' => [$this->alice->id, $this->bob->id]]))
            ->assertViewHas('dayDetails', fn ($details) => collect($details)->pluck('username')->sort()->values()->all() === ['alice', 'bob']);
    }

    public function test_impossible_date_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from($this->calendarUrl())
            ->get($this->calendarUrl(['date' => '2026-02-31']))
            ->assertSessionHasErrors('date');
    }

    // Postman tests 17 and 19: the same URL answers JSON for API clients.
    public function test_date_search_returns_json_for_api_clients(): void
    {
        $this->markBusy($this->alice, '2026-11-20');

        $this->actingAs($this->admin)
            ->getJson($this->calendarUrl(['date' => '2026-11-20']))
            ->assertOk()
            ->assertJsonPath('date', '2026-11-20')
            ->assertJsonCount(3, 'members')
            ->assertJsonFragment(['username' => 'alice', 'busy' => true])
            ->assertJsonFragment(['username' => 'bob', 'busy' => false]);
    }

    public function test_non_member_cannot_search_a_groups_availability(): void
    {
        $this->actingAs($this->makeUser())
            ->getJson($this->calendarUrl(['date' => '2026-11-20']))
            ->assertForbidden();
    }
}
