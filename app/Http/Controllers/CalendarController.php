<?php

namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Models\Availability;
use App\Models\Group;
use App\Models\GroupMember;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function getAvailability(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
            'rangeStart' => 'required|date',
            'rangeEnd' => 'required|date',
        ]);

        $group = Group::find($validated['group_id']);
        if ($group === null) {
            abort(404, 'This group could not be found.');
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
        $monthCarbon = Carbon::create($year, $month, 1);
        $start = $monthCarbon->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $monthCarbon->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $return = CarbonPeriod::create($start, $end);

        return $return;
    }
    public function toggleAvailability(Request $request, Group $group): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You are not a member of this group.');
        }
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);
        $existing = Availability::where('group_id', $group->id)->where('user_id', $user->id)->where('date', $validated['date'])->first();

        if ($existing) {
            $existing->delete();
        } else {
            Availability::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'date' => $validated['date'],
                'status' => 'busy',
            ]);
        }
        if ($request->expectsJson()) {
            return response()->json([
                'busy' => !$existing,   // true if the day is now marked busy
                'busyCount' => Availability::where('group_id', $group->id)
                    ->where('date', $validated['date'])
                    ->count(),
            ]);
        }

        $date = Carbon::parse($validated['date']);

        return redirect("/group/{$group->id}/calendar?year={$date->year}&month={$date->month}");
    }

    public function display(Request $request, Group $group): View
    {

        $user = auth()->user();

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You are not a member of this group.');
        }
        $members = GroupMember::where('group_id', $group->id)->with('user')->get();

        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);
        if ($month < 1 || $month > 12 || $year < 1970 || $year > 2100) {
            $year = now()->year;
            $month = now()->month;
        }
        $first = Carbon::create($year, $month, 1);
        $prev = $first->copy()->subMonth();
        $next = $first->copy()->addMonth();
        //ai code
        $period = $this->cellGenerator($year, $month);

        $busyByDate = Availability::where('group_id', $group->id)
            ->whereBetween('date', [$period->getStartDate()->toDateString(), $period->getEndDate()->toDateString()])
            ->get()
            ->groupBy(fn($row) => $row->date->format('Y-m-d'));

        $cells = [];
        foreach ($period as $day) {
            $busy = $busyByDate->get($day->format('Y-m-d'), collect());

            $cells[] = [
                'date' => $day,
                'inMonth' => $day->month === $month,
                'meBusy' => $busy->contains('user_id', $user->id),
                'busyCount' => $busy->count(),
            ];
        }
        // $cells = [];

        // foreach ($this->cellGenerator($year, $month) as $day) {
        //     $cells[] = [
        //         'date' => $day,
        //         'inMonth' => $day->month === $month,
        //     ];
        // }

        return view('calendar', [
            'group' => $group,
            'membership' => $membership,
            'members' => $members,
            'cells' => $cells,
            'first' => $first,
            'prev' => $prev,
            'next' => $next,
        ]);
    }
}
