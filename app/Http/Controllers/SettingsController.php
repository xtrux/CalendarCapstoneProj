<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function changeUsername(Request $request)
    {   
        $user = auth()->user();
        $validated = $request->validate([
            'username' => "required|string|unique:users,username,{$user->id}",
            
        ]);
        $user->update([
            'username' => $validated['username'],
            
        ]);
        return redirect('/settings')->with('success', 'Username updated!');
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'newPass' => 'required|min:8|regex:/[A-Z]/|confirmed',
        ]);
        $credentials = $request->validate([
            'oldPass' => 'required',
        ]);
        if (Hash::check($credentials['oldPass'], $user->password)) {
            $user->update([
                'password' => $validated['newPass'],

            ]);
        }else{
            return back()->withErrors(['password' => 'old password is invalid']);
        }
        
        return redirect('/settings')->with('success', 'Password updated!');
    }


}
