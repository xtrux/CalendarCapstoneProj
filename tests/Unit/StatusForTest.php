<?php

namespace Tests\Unit;

use App\Http\Controllers\CalendarController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The colour rule for a calendar day (F4.4): how many members are busy
 * compared with how many are in the group (or selected by the filter).
 */
class StatusForTest extends TestCase
{
    public static function cases(): array
    {
        return [
            // label                                busy, members, expected
            'test case 17: nobody busy (0/4)' => [0, 4, 'allFree'],
            'test case 18: half busy (2/4)' => [2, 4, 'half'],
            'test case 19: everyone busy (4/4)' => [4, 4, 'allBusy'],
            'one busy out of four' => [1, 4, 'half'],
            'all but one busy' => [3, 4, 'half'],
            'one-person group, free' => [0, 1, 'allFree'],
            'one-person group, busy' => [1, 1, 'allBusy'],
            'empty selection counts as free' => [0, 0, 'allFree'],
        ];
    }

    #[DataProvider('cases')]
    public function test_status_for(int $busy, int $members, string $expected): void
    {
        $this->assertSame($expected, CalendarController::statusFor($busy, $members));
    }
}
