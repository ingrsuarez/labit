<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'ip_address',
    ];

    public const MODULE_NAMES = [
        'App\Models\Patient' => 'Paciente',
        'App\Models\Admission' => 'Admisión',
        'App\Models\Sample' => 'Muestra',
    ];

    public const ACTION_LABELS = [
        'created' => ['label' => 'Creación', 'color' => 'green'],
        'updated' => ['label' => 'Edición', 'color' => 'blue'],
        'deleted' => ['label' => 'Eliminación', 'color' => 'red'],
        'login' => ['label' => 'Login', 'color' => 'green'],
        'logout' => ['label' => 'Logout', 'color' => 'gray'],
        'login_failed' => ['label' => 'Login fallido', 'color' => 'red'],
        'validated' => ['label' => 'Validación', 'color' => 'teal'],
        'unvalidated' => ['label' => 'Desvalidación', 'color' => 'yellow'],
        'results_loaded' => ['label' => 'Carga de resultados', 'color' => 'blue'],
        'pdf_generated' => ['label' => 'PDF generado', 'color' => 'gray'],
        'email_sent' => ['label' => 'Email enviado', 'color' => 'gray'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getModuleNameAttribute(): string
    {
        return self::MODULE_NAMES[$this->auditable_type] ?? class_basename($this->auditable_type ?? '');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action]['label'] ?? ucfirst($this->action);
    }

    public function getActionColorAttribute(): string
    {
        return self::ACTION_LABELS[$this->action]['color'] ?? 'gray';
    }

    public function getAuditableUrlAttribute(): ?string
    {
        if (! $this->auditable_type || ! $this->auditable_id) {
            return null;
        }

        try {
            $exists = $this->auditable_type::find($this->auditable_id);
            if (! $exists) {
                return null;
            }

            return match ($this->auditable_type) {
                'App\Models\Patient' => route('patient.edit', ['id' => $this->auditable_id]),
                'App\Models\Admission' => route('lab.admissions.show', $this->auditable_id),
                'App\Models\Sample' => route('sample.show', $this->auditable_id),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
