<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    use HasFactory;

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
        'validator_notes',
        'observations',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'sampling_date' => 'date',
        'validated_at' => 'datetime',
    ];

    /**
     * Genera el próximo número de protocolo
     */
    public static function generateProtocolNumber(): string
    {
        $year = date('Y');
        $lastSample = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSample) {
            // Extraer el número del protocolo anterior
            $lastNumber = (int) substr($lastSample->protocol_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
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

    /**
     * Relación con el usuario que creó la muestra
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Calcula el estado real del protocolo basado en determinaciones
     * - Validado: TODAS las determinaciones están validadas
     * - Completo: Todos los resultados cargados O algunas validadas
     * - Incompleto: No tiene todas las determinaciones con resultado
     */
    public function getCalculatedStatusAttribute(): string
    {
        $total = $this->determinations->count();
        if ($total === 0) {
            return 'pending';
        }

        $completed = $this->determinations->where('status', 'completed')->count();
        $validated = $this->determinations->where('is_validated', true)->count();

        // Todas validadas = Validado
        if ($validated === $total && $total > 0) {
            return 'validated';
        }

        // Todas completadas O algunas validadas = Completo
        if ($completed === $total || $validated > 0) {
            return 'completed';
        }

        // No todas completadas = Incompleto
        return 'incomplete';
    }

    /**
     * Obtiene el estado en español
     */
    public function getStatusLabelAttribute(): string
    {
        $calculated = $this->calculated_status;
        
        return match($calculated) {
            'validated' => 'Validado',
            'completed' => 'Completo',
            'incomplete' => 'Incompleto',
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getStatusColorAttribute(): string
    {
        $calculated = $this->calculated_status;
        
        return match($calculated) {
            'validated' => 'green',
            'completed' => 'blue',
            'incomplete' => 'yellow',
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'cancelled' => 'red',
            default => 'gray',
        };
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
        return match($this->validation_status) {
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
        return match($this->validation_status) {
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
            && $this->determinations->every(fn($d) => $d->status === 'completed');
    }

    /**
     * Verifica si el protocolo está listo para descargar/enviar
     */
    public function isReadyForDownload(): bool
    {
        return $this->isValidated();
    }
}
