<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

}
