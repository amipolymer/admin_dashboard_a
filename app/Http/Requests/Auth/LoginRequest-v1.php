<?php
namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\User;


class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Check if the authentication attempt is successful
        if (! Auth::attempt(
            array_merge($this->only('email', 'password'), ['status' => 'active']),
            $this->boolean('remember')
        )) {
            // Log failed login attempt
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account is deactivated or credentials are incorrect.',
            ]);
        }

        $user = Auth::user();

        // Check if the user's password was changed more than 60 days ago
        if (!in_array($user->role, ['admin', 'superadmin']) && $user->password_changed_at && $user->password_changed_at->lt(now()->subDays(60))) {
        // if ($user->password_changed_at && $user->password_changed_at->lt(now()->subDays(1))) {
            // Log password expiration
            redirect()->route('password.change');
            // Logout the user if the password is expired
            Auth::logout();
            session([
                'force_password_change' => true,
                'password_expired_user' => $user->id,
            ]);

            // Throw an exception to inform the user to change their password
            throw ValidationException::withMessages([
                'email' => 'Your password has expired. Please change your password.',
                'link' => route('password.change'),
            ]);
        }

        // Clear the rate limiter for the successful login attempt
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
