<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'test_id',
        'price',
        'nbu_units',
        'authorization_status',
        'paid_by_patient',
        'copago',
        'authorization_code',
        'observations',
        'result',
        'unit',
        'reference_value',
        'is_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'nbu_units' => 'decimal:2',
        'paid_by_patient' => 'boolean',
        'copago' => 'decimal:2',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    /**
     * Estados de autorización disponibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_REJECTED = 'rejected';
    const STATUS_NOT_REQUIRED = 'not_required';

    /**
     * Relación con la admisión
     */
    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Relación con la práctica/test
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Relación con el usuario que validó
     */
    public function validator()
    {
        return $this->belongsTo(\App\Models\User::class, 'validated_by');
    }

    /**
     * Verifica si está autorizado
     */
    public function isAuthorized(): bool
    {
        return $this->authorization_status === self::STATUS_AUTHORIZED 
            || $this->authorization_status === self::STATUS_NOT_REQUIRED;
    }

    /**
     * Verifica si está pendiente
     */
    public function isPending(): bool
    {
        return $this->authorization_status === self::STATUS_PENDING;
    }

    /**
     * Verifica si fue rechazado
     */
    public function isRejected(): bool
    {
        return $this->authorization_status === self::STATUS_REJECTED;
    }

    /**
     * Obtiene el monto que paga la obra social
     */
    public function getInsuranceAmountAttribute(): float
    {
        if ($this->paid_by_patient || $this->isRejected()) {
            return 0;
        }
        return (float) $this->price - (float) $this->copago;
    }

    /**
     * Obtiene el monto que paga el paciente
     */
    public function getPatientAmountAttribute(): float
    {
        if ($this->paid_by_patient || $this->isRejected()) {
            return (float) $this->price;
        }
        return (float) $this->copago;
    }

    /**
     * Obtiene la etiqueta del estado de autorización
     */
    public function getAuthorizationStatusLabelAttribute(): string
    {
        return match($this->authorization_status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_AUTHORIZED => 'Autorizado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_NOT_REQUIRED => 'No requiere',
            default => $this->authorization_status,
        };
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getAuthorizationStatusColorAttribute(): string
    {
        return match($this->authorization_status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_AUTHORIZED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_NOT_REQUIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope para prácticas autorizadas
     */
    public function scopeAuthorized($query)
    {
        return $query->whereIn('authorization_status', [self::STATUS_AUTHORIZED, self::STATUS_NOT_REQUIRED]);
    }

    /**
     * Scope para prácticas pagadas por paciente
     */
    public function scopePaidByPatient($query)
    {
        return $query->where('paid_by_patient', true);
    }

    /**
     * Scope para prácticas pagadas por obra social
     */
    public function scopePaidByInsurance($query)
    {
        return $query->where('paid_by_patient', false)
            ->whereIn('authorization_status', [self::STATUS_AUTHORIZED, self::STATUS_NOT_REQUIRED]);
    }
}

