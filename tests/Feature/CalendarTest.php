<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Calendar month view (F4.2 and the Week 6 grid).
 * Availability marking and colors aren't built yet, so they aren't tested here.
 */
class CalendarTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function calendarUrl(int $groupId, array $query = []): string
    {
        $url = '/group/'.$groupId.'/calendar';

        return $query ? $url.'?'.http_build_query($query) : $url;
    }

    public function test_member_sees_requested_month(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)
            ->get($this->calendarUrl($group->id, ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertViewHas('cells', function (array $cells) {
                return count($cells) === 35
                    && $cells[0]['date']->toDateString() === '2026-09-27'
                    && end($cells)['date']->toDateString() === '2026-10-31';
            });
    }

    public function test_overflow_days_are_marked_outside_the_month(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        // November 2026 runs Nov 1 – Dec 5; Dec 1–5 are overflow.
        $this->actingAs($admin)
            ->get($this->calendarUrl($group->id, ['year' => 2026, 'month' => 11]))
            ->assertViewHas('cells', function (array $cells) {
                $last = end($cells);

                return $cells[0]['inMonth'] === true
                    && $last['date']->toDateString() === '2026-12-05'
                    && $last['inMonth'] === false;
            });
    }

    public function test_defaults_to_current_month(): void
    {
        $this->travelTo(Carbon::create(2026, 3, 15));
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)->get($this->calendarUrl($group->id))
            ->assertOk()
            ->assertSee('March 2026');
    }

    public function test_invalid_month_falls_back_to_current_month(): void
    {
        $this->travelTo(Carbon::create(2026, 3, 15));
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)
            ->get($this->calendarUrl($group->id, ['year' => 2026, 'month' => 13]))
            ->assertOk()
            ->assertSee('March 2026');
    }

    // F4.2: next/previous month, including across a year boundary
    public function test_next_month_rolls_over_into_next_year(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)
            ->get($this->calendarUrl($group->id, ['year' => 2026, 'month' => 12]))
            ->assertViewHas('next', fn ($next) => $next->year === 2027 && $next->month === 1)
            ->assertSee('year=2027&month=1', false);
    }

    public function test_previous_month_rolls_back_into_previous_year(): void
    {
        $admin = $this->makeUser();
        $group = $this->makeGroup($admin);

        $this->actingAs($admin)
            ->get($this->calendarUrl($group->id, ['year' => 2026, 'month' => 1]))
            ->assertViewHas('prev', fn ($prev) => $prev->year === 2025 && $prev->month === 12)
            ->assertSee('year=2025&month=12', false);
    }

    public function test_non_member_cannot_view_calendar(): void
    {
        $group = $this->makeGroup($this->makeUser());

        $this->actingAs($this->makeUser())
            ->get($this->calendarUrl($group->id))
            ->assertForbidden();
    }
}
