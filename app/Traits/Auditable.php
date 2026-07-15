<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     */
    protected static function bootAuditable()
    {
        static::created(fn($model) => self::logActivity($model, 'create'));
        static::updated(fn($model) => self::logActivity($model, 'update'));
        static::deleted(fn($model) => self::logActivity($model, 'delete'));
    }

    /**
     * Log activity for a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $action
     * @return void
     */
    protected static function logActivity($model, $action)
    {
        // Fields to exclude from logging
        $exclude = $model->auditExclude ?? ['password', 'remember_token', 'api_token'];
        $auditAll = $model->auditAllFields ?? false;

        $oldData = null;
        $newData = null;

        // Handle update
        if ($action === 'update') {
            if ($auditAll) {
                $oldData = collect($model->getOriginal())->except($exclude)->toArray();
                $newData = collect($model->toArray())->except($exclude)->toArray();
            } else {
                $oldData = collect($model->getOriginal())
                    ->except($exclude)
                    ->only(array_keys($model->getChanges()))
                    ->toArray();

                $newData = collect($model->getChanges())
                    ->except($exclude)
                    ->toArray();
            }
        }

        // Handle create
        if ($action === 'create') {
            $newData = collect($model->toArray())->except($exclude)->toArray();
        }

        // Handle delete
        if ($action === 'delete') {
            $oldData = collect($model->getOriginal())->except($exclude)->toArray();
        }

        // Detect device/browser/platform
        $agent = new Agent();

        try {
            ActivityLog::create([
                'model' => get_class($model),
                'model_id' => $model->id,
                'action' => $action,
                'old_data' => $oldData,
                'new_data' => $newData,
                'status' => 'success',
                'performed_by' => Auth::id(),
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'browser' => $agent->browser(),
                'platform' => $agent->platform(),
                'device' => $agent->device() ?: 'Desktop',
            ]);
        } catch (\Exception $e) {
            // Optional: log error to system log if ActivityLog fails
            Log::error('Failed to save activity log: '.$e->getMessage());
        }
    }
}
