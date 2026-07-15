<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;
use App\Models\UserLoginLog;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        // ❌ Exclude superadmin
        if ($user->role === 'superadmin') {
            return;
        }

        // Retrieve login_time from session (optional)
        $loginTime = Session::get('login_time');

        // Update the last_logout_at in users table (optional)
        $user->update([
            'last_logout_at' => now(),
        ]);

        // ✅ Update the login log with logout time
        // Assuming the last login log entry is the one for this session
        $lastLog = UserLoginLog::where('user_id', $user->id)
                               ->latest('login_at')
                               ->first();

        if ($lastLog) {
            $lastLog->update([
                'logout_at' => now(),
                'session_duration' => $loginTime ? now()->diffInSeconds($loginTime) : null,
            ]);
        }

        // Clear the session login time
        Session::forget('login_time');
    }
}
