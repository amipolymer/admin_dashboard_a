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

            $loginTime = Session::get('login_time');
         
            // 2 hours = 120 minutes
            if ($loginTime && now()->diffInMinutes($loginTime) >= config('session.lifetime')) {
            
                // ✅ UPDATE logout BEFORE Auth::logout()
                UserLoginLog::where('user_id', $user->id)
                    ->whereNull('logout_at')
                    ->latest('login_at')
                    ->update([
                        'logout_at' => now(),
                        'updated_at' => now(),
                        'logout_reason' => 'auto',
                    ]);
                 
                // Update users table
                $user->update([
                    'last_logout_at' => now(),
                ]);

                // Now logout safely
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return redirect('/login')
                    ->withErrors([
                        'session' => 'You were logged out due to session expiration.',
                    ]);
            }
        }

        return $next($request);
    }
}






