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
    private function generateUniqueInviteCode(): string
    {
        do {
            $code = Str::random(8);
        } while (Group::where('invite_code', $code)->exists());

        return $code;
    }

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
                'invite_code' => $this->generateUniqueInviteCode(),

            ]);
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            return $group;
        });

        return redirect('/group/'.$group->id)->with('success', 'group created');
    }

    public function joinGroup(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'invite_code' => 'required|string|min:8|max:8',
        ]);
        if (Group::where('invite_code', $validated['invite_code'])->first() !== null) {
            $group = Group::where('invite_code', $validated['invite_code'])->first();

            // prevent joining a group multiple times
            if (GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->exists()) {
                return back()->withErrors(['invite_code' => 'You are already a member of this group.']);
            }
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'member',
            ]);
        } else {
            return back()->withErrors(['invite_code' => 'This invite code does not exist.']);
        }

        return redirect('/group/'.$group->id);
    }

    public function leaveGroup(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
        ]);
        $group = Group::find($validated['group_id']);
        if ($group == null) {
            return back()->withErrors(['failed' => 'this group doesnt exist?']);

        }
        $groupMember = GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first();
        if ($groupMember === null) {
            return back()->withErrors(['failed' => 'This user is not a member of this group.']);

        } else {
            $groupMember->delete();

        }

        return redirect('/');
    }

    public function removeUser(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
            'removed_id' => 'required',
        ]);
        $group = Group::find($validated['group_id']);
        if ($group === null) {
            return back()->withErrors(['failed' => 'this group does not exist']);
        } else {
            if ($group->admin_user_id == $user->id) {
                $groupMember = GroupMember::where('group_id', $group->id)->where('user_id', $validated['removed_id'])->first();
                if ($groupMember === null) {
                    return back()->withErrors(['failed' => 'This user is not a member of this group.']);
                } else {
                    $groupMember->delete();

                }
            } else {
                return back()->withErrors(['failed' => 'Only the group admin can remove members.']);
            }
        }

        return redirect('/group/'.$group->id);
    }

    public function deleteGroup(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
        ]);
        $group = Group::find($validated['group_id']);
        if ($group === null) {
            return back()->withErrors(['failed' => 'this group does not exist']);
        } else {
            if ($group->admin_user_id == $user->id) {
                $group->delete();

                return redirect('/');
            }

        }

        return back()->withErrors(['failed' => 'Only the group admin can delete this group.']);

    }

    public function regenerateInvite(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'group_id' => 'required',
        ]);
        $group = Group::find($validated['group_id']);
        if ($group === null) {
            return back()->withErrors(['failed' => 'this group does not exist']);
        } else {
            if ($group->admin_user_id == $user->id) {
                $code = $this->generateUniqueInviteCode();
                $group->update(['invite_code' => $code]);

                return redirect('/');
            }

        }

        return back()->withErrors(['failed' => 'Only the group admin can regenerate the invite.']);
    }
}
