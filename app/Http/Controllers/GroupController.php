<?php

namespace App\Http\Controllers;

use App\Models\Group;
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
        $group =Group::create([
            'name' => $validated['name'],
            'admin_user_id' => $user->id,
            'invite_code' => Str::random(8),

        ]);

        return redirect('/group/' . $group->id)->with('success', 'group created');
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
