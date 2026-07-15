<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'model',
        'model_id',
        'action',
        'old_data',
        'new_data',
        'status',
        'performed_by',
        'ip_address',
        'browser',
        'platform',
        'device'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Distinct model class names for filter dropdown (DB + known auditable models).
     *
     * @return list<string>
     */
    public static function modelOptions(?Carbon $since = null): array
    {
        $since = $since ?? now()->subMonths(6)->startOfDay();

        $fromDb = self::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        $known = [
            \App\Models\User::class,
            \App\Models\EmployeesNewJoiner::class,
            \App\Models\NewEmployeesDocument::class,
            \App\Models\Employee::class,
            \App\Models\QuickLink::class,
            \App\Models\RoleRoutePermission::class,
            \App\Models\AnnualReportViewForm::class,
        ];

        return $fromDb->merge($known)->unique()->sort()->values()->all();
    }
}
