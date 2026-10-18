<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
    public function changeEmail(Request $request)
    {   
        $user = auth()->user();
        $validated = $request->validate([
            'email' => "required|string|email|max:255|unique:users,email,{$user->id}",
            
        ]);
        $user->update([
            'email' => $validated['email'],
            
        ]);
        return redirect('/settings')->with('success', 'email updated!');
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
                // dd('wrong password branch reached');
            return back()->withErrors(['password' => 'Old Password is Incorrect']);
        }
        
        return redirect('/settings')->with('success', 'Password updated!');
    }
    public function changeProfilePicture(Request $request){
        $user = auth()->user();
        $validated = $request->validate([
            'profile_picture' => "required|image|mimes:jpg,jpeg,png,webp|max:2048",
        ]);
    }
    

}
