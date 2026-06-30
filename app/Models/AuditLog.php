<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Obtener el modelo auditado
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Obtener el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crear un registro de auditoría
     */
    public static function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): static {
        return static::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
