<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

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
        $credentials = $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required',
            ]
        );
        if (User::attempt($credentials,$request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect('')->intended('/')->with('success','you are logged in');
    }
}
}
