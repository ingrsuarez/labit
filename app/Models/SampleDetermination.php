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
        'price',
        'status',
        'result',
        'unit',
        'reference_value',
        'reference_category_id',
        'method',
        'observations',
        'analyzed_by',
        'analyzed_at',
        'is_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_validated' => 'boolean',
        'price' => 'decimal:2',
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
     * Relación con la categoría de referencia usada
     */
    public function referenceCategory()
    {
        return $this->belongsTo(ReferenceCategory::class, 'reference_category_id');
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

    public function hasResult(): bool
    {
        return $this->result !== null && $this->result !== '';
    }

    /**
     * Relación con el usuario validador
     */
    public function determinationValidator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
