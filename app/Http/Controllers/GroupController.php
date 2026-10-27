<?php

namespace App\Http\Controllers;

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

    public function joinGroup(Request $request): RedirectResponse {}

    public function leaveGroup(Request $request): ReturnType {}

    public function removeUser(): ReturnType {}

    public function deleteGroup(): ReturnType {}

    public function regenerateInvite(): ReturnType {}
}
