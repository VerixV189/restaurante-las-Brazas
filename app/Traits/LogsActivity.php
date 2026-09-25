<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::recordActivity($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();

            // Evitar registrar si no hubo cambios reales excepto timestamps
            unset($oldValues['updated_at'], $newValues['updated_at']);

            if (!empty($newValues)) {
                static::recordActivity($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            static::recordActivity($model, 'deleted', $model->getAttributes(), null);
        });
    }

    protected static function recordActivity($model, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->header('User-Agent') : null,
            ]);
        } catch (\Throwable $e) {
            // Prevenir que un error en el log rompa la transacción principal
            logger()->error('Error grabando ActivityLog: ' . $e->getMessage());
        }
    }
}
