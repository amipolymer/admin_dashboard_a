<?php

namespace App\Listeners;

use App\Models\UserLoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // ❌ Exclude superadmin
        if ($user->role === 'superadmin') {
            return;
        }

        // Store login time for auto-logout
        Session::put('login_time', now());

        // Update quick-access users table
        $user->update([
            'last_login_at' => now(),
        ]);

        $agent = new Agent();

        UserLoginLog::create([
            'user_id'    => $user->id,
            'login_at'   => now(),
            'ip_address' => request()->ip(),
            'browser'    => $agent->browser() ?: request()->userAgent(),
            'platform'   => $agent->platform(),
            'device'     => $agent->device() ?: 'Desktop',
        ]);
    }
}
