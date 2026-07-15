<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Models\UserLoginLog;
use App\Traits\Auditable;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Auditable;


    protected $auditAllFields = false;   
    protected $auditExclude = ['password', 'remember_token', 'api_token'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phoneno',
        'emp_id',
        'status',
        'profile_image',
        'role',
        'email_verified_at',
        'password_changed_at',
        'remember_token',
        'is_locked',
        'locked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',  // we need nullable datetime
        ];
    }
    
    public function loginLogs()
    {
       return $this->hasMany(UserLoginLog::class);
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

   public function passwordExpired(): bool
    {
        if (!$this->password_changed_at) {
            return true; // never changed
        }
    
        return $this->password_changed_at->addDays(60)->isPast();
    }


   
}
