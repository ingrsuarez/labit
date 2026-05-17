<?php

namespace App\Models;

use App\Services\ProtocolStatusCalculator;
use App\Traits\Auditable;
use App\Traits\GeneratesProtocolNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    use Auditable, GeneratesProtocolNumber, HasFactory;

    protected $fillable = [
        'protocol_number',
        'sample_type',
        'entry_date',
        'sampling_date',
        'customer_id',
        'location',
        'address',
        'batch',
        'product_name',
        'status',
        'validation_status',
        'validated_by',
        'validated_at',
        'sent_at',
        'validator_notes',
        'observations',
        'created_by',
        'lab_branch_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'sampling_date' => 'date',
        'validated_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Genera el próximo número de protocolo
     */
    public static function generateProtocolNumber(): string
    {
        return static::generatePrefixedProtocolNumber('A');
    }

    /**
     * Relación con el cliente
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con las determinaciones
     */
    public function determinations()
    {
        return $this->hasMany(SampleDetermination::class);
    }

    public function determinationProfileApplications()
    {
        return $this->morphMany(DeterminationProfileApplication::class, 'applicable')->latest();
    }

    /**
     * Relación con el usuario que creó la muestra
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labBranch()
    {
        return $this->belongsTo(LabBranch::class);
    }

    /**
     * Verifica si es una muestra de agua
     */
    public function isWater(): bool
    {
        return $this->sample_type === 'agua';
    }

    /**
     * Verifica si es una muestra de alimento
     */
    public function isFood(): bool
    {
        return $this->sample_type === 'alimento';
    }

    /**
     * Estado de trabajo calculado a partir de determinaciones (envío aparte: sent_at).
     */
    public function getCalculatedStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        return app(ProtocolStatusCalculator::class)->calculate($this->determinations);
    }

    /**
     * Obtiene el estado en español
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->calculated_status === 'cancelled') {
            return 'Cancelado';
        }

        return ProtocolStatusCalculator::labelFor($this->calculated_status);
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->calculated_status === 'cancelled') {
            return 'red';
        }

        return ProtocolStatusCalculator::colorFor($this->calculated_status);
    }

    /**
     * Relación con el usuario validador
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Obtiene el estado de validación en español
     */
    public function getValidationStatusLabelAttribute(): string
    {
        return match ($this->validation_status) {
            'pending' => 'Sin validar',
            'partial' => 'Parcialmente validado',
            'validated' => 'Validado',
            'rejected' => 'Rechazado',
            default => $this->validation_status ?? 'Pendiente',
        };
    }

    /**
     * Obtiene el color del badge de validación
     */
    public function getValidationStatusColorAttribute(): string
    {
        return match ($this->validation_status) {
            'pending' => 'gray',
            'partial' => 'blue',
            'validated' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Verifica si el protocolo está completamente validado (todas las determinaciones)
     */
    public function isValidated(): bool
    {
        return $this->validation_status === 'validated';
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * Verifica si tiene al menos una determinación validada
     */
    public function hasValidatedDeterminations(): bool
    {
        return $this->determinations->where('is_validated', true)->count() > 0;
    }

    /**
     * Verifica si el protocolo puede ser validado
     */
    public function canBeValidated(): bool
    {
        // Solo se puede validar si todas las determinaciones están completadas
        return $this->status === 'completed'
            && $this->validation_status === 'pending'
            && $this->determinations->every(fn ($d) => $d->status === 'completed');
    }

    /**
     * Verifica si el protocolo está listo para descargar/enviar
     */
    public function isReadyForDownload(): bool
    {
        return $this->isValidated();
    }

    public function invoiceProtocols()
    {
        return $this->morphMany(InvoiceProtocol::class, 'protocol');
    }

    public function salesInvoices()
    {
        return $this->morphToMany(SalesInvoice::class, 'protocol', 'invoice_protocols')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function isInvoiced(): bool
    {
        return $this->invoiceProtocols()->exists();
    }

    public function scopeUninvoiced($query)
    {
        return $query->whereDoesntHave('invoiceProtocols');
    }

    public function scopeInvoiced($query)
    {
        return $query->whereHas('invoiceProtocols');
    }
}
