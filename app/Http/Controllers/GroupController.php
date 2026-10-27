<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function createGroup(Request $request): RedirectResponse
    {
        Group::create([

        ]);

        return redirect('/group/{id}')->with('success', 'group created');
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
