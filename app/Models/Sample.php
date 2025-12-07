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
        'observations',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'sampling_date' => 'date',
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
     * Obtiene el estado en español
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}
