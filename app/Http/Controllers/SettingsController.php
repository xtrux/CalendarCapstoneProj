<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
class SettingsController extends Controller
{
    public function changeUsername(Request $request): RedirectResponse
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

    public function changeEmail(Request $request): RedirectResponse
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

    public function deleteUser(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $credentials = $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($credentials['password'], $user->password)) {
            Auth::logout();
            $user->delete();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('success', 'Your account has been deleted.');
        } else {
            return back()->withErrors(['password' => 'Password is Incorrect']);
        }
    }

    public function changePassword(Request $request): RedirectResponse
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
        } else {
            // dd('wrong password branch reached');
            return back()->withErrors(['password' => 'Old Password is Incorrect']);
        }

        return redirect('/settings')->with('success', 'Password updated!');
    }

    // to finish
    public function changeProfilePicture(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }
         $file = $request->file('profile_picture');
    $filename = Str::random(40) . '.' . $file->extension();
    $path = 'profile_pictures/' . $filename;

    Storage::disk('public')->put($path, file_get_contents($file->getPathname()));

    $user->update(['profile_picture' => $path]);

        


        return redirect('/settings')->with('success', 'Profile Picture Updated!');
    }
}
