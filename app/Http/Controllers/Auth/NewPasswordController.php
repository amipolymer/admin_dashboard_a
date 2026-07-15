<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    // Validate input
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => [
            'required',
            'string',
            'confirmed',
            'min:8',
            'max:15',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,15}$/',
        ],
    ], [
        'password.regex' => 'Password must be 8–15 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.',
        'password.min' => 'Password must be at least 8 characters.',
        'password.max' => 'Password may not be greater than 15 characters.',
        'password.confirmed' => 'Password confirmation does not match.',
        'token.required' => 'Password reset token is required.',
        'email.required' => 'Email is required.',
        'email.email' => 'Please provide a valid email address.',
    ]);

    // Attempt to reset password
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user) use ($request) {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
                'password_changed_at' => now(),
            ])->save();

            event(new PasswordReset($user));
        }
    );

    // Check status and return human-readable message
    switch ($status) {
        case Password::PASSWORD_RESET:
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully.')
                ->with('toast_success', 'Password reset successfully.')
                ->with('status_bg', 'success');

        case Password::INVALID_TOKEN:
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'This password reset link is invalid or expired.')
                ->with('status_bg', 'danger');

        case Password::INVALID_USER:
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'We could not find a user with that email address.')
                ->with('status_bg', 'danger');

        default:
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'Failed to reset password. Please try again.')
                ->with('status_bg', 'danger');
    }
}
}
