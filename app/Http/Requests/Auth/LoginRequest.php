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
    public function authorize(): bool
    {
        return true;
    }

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

          $user = User::where('email', $this->email)->first();
          $lockDuration = 2; // minutes
          $lockedAt = $user->locked_at ? Carbon::parse($user->locked_at) : null;
             if ($user && $user->is_locked) {
          if ($lockedAt && Carbon::now()->greaterThanOrEqualTo($lockedAt->addMinutes($lockDuration))) {
              // Auto-unlock
              $user->update([
                  'is_locked' => false,
                  'locked_at' => null,
              ]);
              logger()->info('Auto-unlocked user: ' . $user->email);
              RateLimiter::clear($this->throttleKey());
          } else {
              // Remaining minutes
              $minutesLeft = $lockedAt ? ceil($lockedAt->diffInSeconds(now()) / 60) : $lockDuration;
              throw ValidationException::withMessages([
                  'email' => 'Your account is locked. Please try again after ' . max($minutesLeft, 1) . ' minute(s).',
              ]);
          }
        }
        // Attempt login
        $credentials = array_merge($this->only('email', 'password'), ['status' => 'active']);
        if (!Auth::attempt($credentials, $this->boolean('remember'))) {

            RateLimiter::hit($this->throttleKey());

            // Lock user after 5 failed attempts
            if ($user && RateLimiter::attempts($this->throttleKey()) >= 5 && !$user->is_locked) {
                $user->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                ]);
                logger()->info('User locked due to failed attempts: ' . $user->email);
            }

            throw ValidationException::withMessages([
                'email' => 'Your account is deactivated or credentials are incorrect.',
            ]);
        }

        //  Successful login
        $user = Auth::user();
        RateLimiter::clear($this->throttleKey());

        // Password expiry check
        if (!in_array($user->role, ['admin', 'superadmin']) &&
            $user->password_changed_at &&
            $user->password_changed_at->lt(now()->subDays(60))
        ) {
            Auth::logout();

            session([
                'force_password_change' => true,
                'password_expired_user' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'email' => 'Your password has expired. Please change your password.',
                'link'  => route('password.change'),
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 4)) {
            return;
        }

        $user = User::where('email', $this->email)->first();
        logger()->info('Locking user due to rate limit exceeded: ' . ($user ? $user->email : 'unknown'));

        // Lock user in DB when rate limit is exceeded
        if ($user && !$user->is_locked) {
            $user->update([
                'is_locked' => true,
                'locked_at' => now(),
            ]);
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
