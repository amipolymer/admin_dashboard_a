<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\UserLoginLog;

class AutoLogout
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {

            $user = Auth::user();

            // Exclude superadmin
            if ($user->role === 'superadmin') {
                return $next($request);
            }

            $timeout = 5 * 60; // 5 minutes (seconds)
            $lastActivity = Session::get('last_activity');

            if ($lastActivity && (time() - $lastActivity) > $timeout) {

                // ✅ Update logout log BEFORE logout
                UserLoginLog::where('user_id', $user->id)
                    ->whereNull('logout_at')
                    ->latest('login_at')
                    ->update([
                        'logout_at' => now(),
                        'updated_at' => now(),
                        'logout_reason' => 'idle_auto',
                    ]);

                // Update users table
                $user->update([
                    'last_logout_at' => now(),
                ]);

                // Logout
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return redirect('/login')->withErrors([
                    'session' => 'You were logged out due to 5 minutes of inactivity.',
                ]);
            }

            // Update last activity timestamp
            Session::put('last_activity', time());
        }

        return $next($request);
    }
}
