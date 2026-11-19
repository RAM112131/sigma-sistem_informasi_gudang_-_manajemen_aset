<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Loggable
{
    /**
     * Boot the trait.
     */
    protected static function bootLoggable(): void
    {
        // Listen to model events
        static::created(function ($model) {
            static::logActivity($model, 'created');
        });

        static::updated(function ($model) {
            static::logActivity($model, 'updated');
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted');
        });
    }

    /**
     * Log an activity for the model.
     *
     * @param  mixed  $model
     */
    protected static function logActivity($model, string $action): void
    {
        // Determine the causer (authenticated user) if any
        $causer = Auth::user();
        $causerType = $causer ? get_class($causer) : User::class;
        $causerId = $causer ? $causer->id : 1;

        // Get IP address and user agent
        $ipAddress = Request::ip();
        $userAgent = Request::header('User-Agent');

        // Determine description based on action and model
        $description = static::getDescriptionForAction($action, $model);

        // Get changes (only for updated)
        $changes = null;
        if ($action === 'updated' && $model instanceof Model) {
            $changes = [
                'sebelum' => $model->getOriginal(),
                'sesudah' => $model->getAttributes(),
            ];
        }

        // Create audit log entry
        AuditLog::create([
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'changes' => $changes ? json_encode($changes) : null,
        ]);
    }

    /**
     * Return a human‑readable description for the action/model.
     *
     * @param  mixed  $model
     */
    protected static function getDescriptionForAction(string $action, $model): string
    {
        $modelClass = class_basename($model);

        return match ($action) {
            'created' => "Menambahkan {$modelClass} baru",
            'updated' => "Mengubah {$modelClass}",
            'deleted' => "Menghapus {$modelClass}",
            default => "{$action} {$modelClass}",
        };
    }
}
