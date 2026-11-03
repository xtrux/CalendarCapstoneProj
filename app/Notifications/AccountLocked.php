<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::sendResetLink($request->only('email'));

        // Same message whether or not the email has an account, so this form
        // can't be used to find out who is registered.
        return back()->with('success', 'If that email has an account, a reset link is on its way.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|regex:/[A-Z]/|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // forceFill because these columns aren't in User's #[Fillable] list.
                // Resetting the password also clears any lockout.
                $user->forceFill([
                    'password' => $password, // hashed by the model's 'hashed' cast
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->save();
            }
        );

        if ($status === Password::PasswordReset) {
            return redirect('/login')->with('success', 'Your password has been reset. You can log in now.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
