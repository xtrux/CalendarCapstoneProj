<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Str;

class GroupController extends Controller
{
    public function createGroup(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string',
        ]);
        $group = DB::transaction(function () use ($user, $validated) {
            $group = Group::create([
                'name' => $validated['name'],
                'admin_user_id' => $user->id,
                'invite_code' => Str::random(8),

            ]);
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);
            return $group;
        });


        return redirect(`/group/` . $group->id)->with('success', 'group created');
    }

    public function joinGroup(Request $request): RedirectResponse
    {
        return redirect('group/{id}');
    }

    public function leaveGroup(Request $request): RedirectResponse
    {
        return redirect('/groups');
    }

    // public function removeUser(): ReturnType {}

    public function deleteGroup(): RedirectResponse
    {
        return redirect('/groups');
    }

    // public function regenerateInvite(): ReturnType {}
}
