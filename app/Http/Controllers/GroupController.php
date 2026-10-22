<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function createGroup(Request $request): RedirectResponse
    {

        return redirect('/group/{id}');
    }
}
