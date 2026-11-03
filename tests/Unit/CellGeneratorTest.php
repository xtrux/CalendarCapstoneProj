<?php

namespace Tests\Unit;

use App\Http\Controllers\CalendarController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The date math behind the month grid. No database or HTTP involved.
 */
class CellGeneratorTest extends TestCase
{
    public static function months(): array
    {
        return [
            // label                          year, month, first day,    last day,     cells
            'Oct 2026 (starts mid-week)' => [2026, 10, '2026-09-27', '2026-10-31', 35],
            'Feb 2026 (no overflow)' => [2026, 2, '2026-02-01', '2026-02-28', 28],
            'Nov 2026 (trailing overflow)' => [2026, 11, '2026-11-01', '2026-12-05', 35],
            'Dec 2026 (year rollover)' => [2026, 12, '2026-11-29', '2027-01-02', 35],
            'Feb 2024 (leap year)' => [2024, 2, '2024-01-28', '2024-03-02', 35],
            'Aug 2026 (six rows)' => [2026, 8, '2026-07-26', '2026-09-05', 42],
        ];
    }

    #[DataProvider('months')]
    public function test_grid_boundaries(int $year, int $month, string $first, string $last, int $count): void
    {
        $days = iterator_to_array((new CalendarController)->cellGenerator($year, $month), false);

        $this->assertCount($count, $days);
        $this->assertSame($first, $days[0]->toDateString());
        $this->assertSame($last, end($days)->toDateString());
    }

    #[DataProvider('months')]
    public function test_grid_is_whole_weeks_starting_sunday(int $year, int $month): void
    {
        $days = iterator_to_array((new CalendarController)->cellGenerator($year, $month), false);

        $this->assertSame(0, count($days) % 7);
        $this->assertTrue($days[0]->isSunday());
        $this->assertTrue(end($days)->isSaturday());
    }
}
