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
use Illuminate\View\View;
use Illuminate\Support\Collection;
class CalendarController extends Controller
{
    // public function getAvailability(Request $request)
    // {
    //     $user = auth()->user();
    //     $validated = $request->validate([
    //         'group_id' => 'required',
    //         'rangeStart' => 'required|date',
    //         'rangeEnd' => 'required|date',
    //     ]);

    //     $group = Group::find($validated['group_id']);
    //     if ($group === null) {
    //         abort(404, 'This group could not be found.');
    //     }
    //     $membership = GroupMember::where('group_id', $group->id)
    //         ->where('user_id', $user->id)
    //         ->first();

    //     if ($membership === null) {
    //         abort(403, 'You are not a member of this group.');
    //     }

    //     $rangeStart = $validated['rangeStart'];
    //     $rangeEnd = $validated['rangeEnd'];
    //     $availability = Availability::where('group_id', $group->id)->whereBetween('date', [$rangeStart, $rangeEnd])->get();
    //     $byDate = $availability->groupBy(fn($row) => $row->date->format('Y-m-d'));
    //     return $byDate;
    // }

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
        $existing = Availability::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereDate('date', $validated['date'])
            ->first();
        // $existing = Availability::where('group_id', $group->id)->where('user_id', $user->id)->where('date', $validated['date'])->first();

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
        $memberIds = GroupMember::where('group_id', $group->id)->pluck('user_id');
        $selectedIds = $this->selectedUserIds($request, $memberIds);

        if ($request->expectsJson()) {
            $busyCount = Availability::where('group_id', $group->id)
                ->whereDate('date', $validated['date'])
                ->whereIn('user_id', $selectedIds)
                ->count();

            return response()->json([
                'busy' => !$existing,
                'busyCount' => $busyCount,
                'memberCount' => $selectedIds->count(),
                'status' => self::statusFor($busyCount, $selectedIds->count()),
            ]);
        }

        $date = Carbon::parse($validated['date']);

        return redirect("/group/{$group->id}/calendar?" . http_build_query([
            'year' => $date->year,
            'month' => $date->month,
            'users' => $request->input('users', []),
        ]));

    }
    public static function statusFor(int $busyCount, int $memberCount): string
    {
        if ($busyCount == 0) {
            return "allFree";
        } else if ($busyCount == $memberCount) {
            return "allBusy";
        } else {
            return "half";
        }
    }
    private function selectedUserIds(Request $request, Collection $memberIds): Collection
    {
        $selected = collect($request->input('users', []))
            ->map(fn($id) => (int) $id)
            ->intersect($memberIds)   // ignore IDs that aren't in this group
            ->values();

        return $selected->isEmpty() ? $memberIds : $selected;
    }
    public function display(Request $request, Group $group): View|JsonResponse
    {

        $user = auth()->user();

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'You are not a member of this group.');
        }
        $members = GroupMember::where('group_id', $group->id)->with('user')->get();

        // filtering
        $memberIds = $members->pluck('user_id');
        $selectedIds = $this->selectedUserIds($request, $memberIds);


        $request->validate([
            'date' => 'nullable|date_format:Y-m-d|after:1969-12-31|before:2101-01-01',
        ]);
        $selectedDate = $request->filled('date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay()
            : null;




        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);
        if ($selectedDate) {
            $year = $selectedDate->year;
            $month = $selectedDate->month;
        }
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
            // ->whereIn('user_id', $selectedIds)->get()
            ->get()

            ->groupBy(fn($row) => $row->date->format('Y-m-d'));

        $cells = [];
        foreach ($period as $day) {
            $all = $busyByDate->get($day->format('Y-m-d'), collect());
            $busy = $all->whereIn('user_id', $selectedIds);
            $cells[] = [
                'date' => $day,
                'inMonth' => $day->month === $month,
                'selected' => $selectedDate !== null && $day->isSameDay($selectedDate),
                'meBusy' => $all->contains('user_id', $user->id),
                'busyCount' => $busy->count(),
                'status' => self::statusFor($busy->count(), $selectedIds->count()),
            ];
        }


        // ai code
        $dayDetails = null;
        if ($selectedDate) {
            $busyIds = $busyByDate->get($selectedDate->format('Y-m-d'), collect())->pluck('user_id');

            $dayDetails = $members
                ->filter(fn($m) => $selectedIds->contains($m->user_id))
                ->map(fn($m) => [
                    'username' => $m->user->username,
                    'busy' => $busyIds->contains($m->user_id),
                ])
                ->sortBy('busy')
                ->values();
        }
        if ($request->expectsJson() && $selectedDate) {
            return response()->json([
                'date' => $selectedDate->toDateString(),
                'members' => $dayDetails,
            ]);
        }

        return view('calendar', [
            'group' => $group,
            'membership' => $membership,
            'members' => $members,
            'selectedIds' => $selectedIds,
            'selectedDate' => $selectedDate,
            'dayDetails' => $dayDetails,
            'cells' => $cells,
            'first' => $first,
            'prev' => $prev,
            'next' => $next,
        ]);
    }
}
