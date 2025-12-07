<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleDetermination extends Model
{
    use HasFactory;

    protected $fillable = [
        'sample_id',
        'test_id',
        'status',
        'result',
        'unit',
        'reference_value',
        'method',
        'observations',
        'analyzed_by',
        'analyzed_at',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
    ];

    /**
     * Relación con la muestra
     */
    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    /**
     * Relación con el test/determinación
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Relación con el usuario que realizó el análisis
     */
    public function analyzer()
    {
        return $this->belongsTo(User::class, 'analyzed_by');
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
            default => $this->status,
        };
    }
}
