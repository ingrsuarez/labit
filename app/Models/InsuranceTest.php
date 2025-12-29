<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'test_id',
        'nbu_units',
        'price',
        'requires_authorization',
        'copago',
        'observations',
    ];

    protected $casts = [
        'nbu_units' => 'decimal:2',
        'price' => 'decimal:2',
        'requires_authorization' => 'boolean',
        'copago' => 'decimal:2',
    ];

    /**
     * Relación con la obra social
     */
    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    /**
     * Relación con la práctica/test
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Scope para buscar por obra social
     */
    public function scopeForInsurance($query, int $insuranceId)
    {
        return $query->where('insurance_id', $insuranceId);
    }

    /**
     * Scope para prácticas que requieren autorización
     */
    public function scopeRequiresAuth($query)
    {
        return $query->where('requires_authorization', true);
    }

    /**
     * Obtiene el precio calculado (si no está definido, calcula por NBU)
     */
    public function getCalculatedPriceAttribute(): float
    {
        if ($this->price) {
            return (float) $this->price;
        }

        $nbuValue = $this->insurance->nbu_value ?? 0;
        return $this->nbu_units * $nbuValue;
    }
}

