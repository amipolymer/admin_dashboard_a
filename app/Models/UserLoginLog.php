<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginLog extends Model
{
    protected $table = 'user_login_logs';

    protected $fillable = [
        'user_id',
        'login_at',
        'logout_at',
        'ip_address',
        'browser',
        'platform',
        'device',
        'location',
        'logout_reason',
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
