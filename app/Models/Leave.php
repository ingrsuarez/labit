<?php

namespace App\Models;

use App\Services\WorkingDaysService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'doctor',
        'start',
        'end',
        // 'days' y 'year' son columnas generadas (STORED GENERATED) - no incluir
        'hour_50',
        'hour_100',
        'description',
        'file',
        'user_id',
        'status',
        'is_justified', // Licencia justificada manualmente (se paga aunque no tenga certificado)
        'approved_by',
        'approved_at',
        'rejection_reason',
        'requested_at',
        'signature_required',
        'signed_at',
    ];

    protected $casts = [
        'start' => 'date',
        'end' => 'date',
        'approved_at' => 'datetime',
        'requested_at' => 'datetime',
        'signed_at' => 'datetime',
        'signature_required' => 'boolean',
        'is_justified' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeVacaciones($query)
    {
        return $query->where('type', 'vacaciones');
    }

    public function scopePendiente($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeAprobado($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeFuture($query)
    {
        return $query->where('start', '>=', now());
    }

    /**
     * Obtener días corridos de la licencia.
     * Las vacaciones se toman en días corridos (LCT Art. 150), al igual que otros tipos.
     */
    public function getWorkingDaysAttribute(): int
    {
        if (! $this->start || ! $this->end) {
            return 0;
        }

        return $this->start->diffInDays($this->end) + 1;
    }

    /**
     * Obtener el detalle de días (total, hábiles, fines de semana, feriados)
     */
    public function getDaysDetailAttribute(): array
    {
        if (! $this->start || ! $this->end) {
            return ['total' => 0, 'working' => 0, 'weekends' => 0, 'holidays' => 0];
        }

        return WorkingDaysService::getDaysDetail($this->start, $this->end);
    }

    /**
     * Días a usar para cálculos (días corridos para todos los tipos)
     */
    public function getEffectiveDaysAttribute(): int
    {
        return $this->working_days;
    }
}
