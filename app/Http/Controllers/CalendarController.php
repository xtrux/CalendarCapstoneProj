<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Group;
use App\Models\GroupMember;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;
use Illuminate\View\View;


class CalendarController extends Controller
{
    public function getAvailability(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
            'rangeStart' => 'required|date',
            'rangeEnd' => 'required|date'
        ]);

        $group = Group::find($validated['group_id']);
        if ($group === null) {
            return;
        }
        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You are not a member of this group.');
        }

        $rangeStart = $validated['rangeStart'];
        $rangeEnd = $validated['rangeEnd'];
        $availability = Availability::where('group_id', $group->id)->whereBetween('date', [$rangeStart, $rangeEnd])->get();
        $byDate = $availability->groupBy(fn($row) => $row->date->format('Y-m-d'));
        return $byDate;
    }

    public function cellGenerator(int $year, int $month): CarbonPeriod
    {
        $monthCarbon = Carbon::create($year,$month,1);
        $start = $monthCarbon->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $monthCarbon->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $return = CarbonPeriod::create($start,$end);
        return $return;
    }
    public function display(Group $group): View
    {
        $user = auth()->user();

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You are not a member of this group.');
        }

        $members = GroupMember::where('group_id', $group->id)->with('user')->get();

        return view('calendar', [
            'group' => $group,
            'membership' => $membership,
            'members' => $members,
        ]);
    }

    
    

}
