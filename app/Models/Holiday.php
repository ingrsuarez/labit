<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'type',
        'is_optional',
    ];

    protected $casts = [
        'date' => 'date',
        'is_optional' => 'boolean',
    ];

    /**
     * Scope para feriados obligatorios
     */
    public function scopeObligatory($query)
    {
        return $query->where('is_optional', false);
    }

    /**
     * Scope para un año específico
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }
}
