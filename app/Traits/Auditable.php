<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLog::log('created', $model, null, $model->toArray());
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();
            $original = $model->getOriginal($dirty);
            
            AuditLog::log('updated', $model, $original, $dirty);
        });

        static::deleted(function (Model $model) {
            AuditLog::log('deleted', $model, $model->toArray(), null);
        });
    }

    /**
     * Obtener registros de auditoría del modelo
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
