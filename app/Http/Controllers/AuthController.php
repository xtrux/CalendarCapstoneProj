<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|min:8|regex:/[A-Z]/|confirmed',
        ]);
        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            // laravel hashes it automatically with the user model 
            'password' => $validated['password'],

        ]);
        return redirect('/login')->with('success', 'Account created! Please log in.');
    }



    public function login(Request $request)
    {

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $credentials['email'])->first();
        if ($user == null) {
            return back()->withErrors(['email' => 'invalid email or passsword']);
        }
        if ($user->locked_until && $user->locked_until->isFuture()) {
            return back()->withErrors(['locked_until' => 'Account is currently locked']);
        }
        if (Hash::check($credentials['password'], $user->password)) {
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/')->with('success', 'Logged in successfully');
        }
        //  if (Auth::attempt($credentials, $request->boolean('remember'))) {
        //          $user->update([
        //              'failed_login_attempts' => 0,
        //            'locked_until' => null,
        //          ]);
        //         $request->session()->regenerate();
        //         return redirect('')->intended('/')->with('success', 'you are logged in');
        //     }
        else {
            $user->failed_login_attempts += 1;
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = now()->addMinutes(60);
            }
            $user->save();
            return back()->withErrors(['password' => 'invalid email or passsword']);
        }
    }
}
