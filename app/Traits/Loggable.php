<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
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
        // Determine the causer (authenticated user) if any — null in CLI/queue context
        $causer = Auth::user();
        $causerType = $causer !== null ? get_class($causer) : null;
        $causerId = $causer?->id;

        // Get IP address and user agent
        $ipAddress = Request::ip();
        $userAgent = Request::header('User-Agent');

        // Determine description based on action and model
        $description = static::getDescriptionForAction($action, $model);

        // Get changes (only for updated), stripping hidden/sensitive fields
        $changes = null;
        if ($action === 'updated' && $model instanceof Model) {
            $changes = [
                'sebelum' => static::sanitizeAttributes($model, $model->getOriginal()),
                'sesudah' => static::sanitizeAttributes($model, $model->getAttributes()),
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
            'changes' => $changes !== null ? json_encode($changes) : null,
        ]);
    }

    /**
     * Strip model's hidden fields from an attribute array before storing.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function sanitizeAttributes(Model $model, array $attributes): array
    {
        return array_diff_key($attributes, array_flip($model->getHidden()));
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
