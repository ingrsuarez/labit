<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestReferenceValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'reference_category_id',
        'value',
        'min_value',
        'max_value',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relación con el test
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Relación con la categoría de referencia
     */
    public function category()
    {
        return $this->belongsTo(ReferenceCategory::class, 'reference_category_id');
    }

    /**
     * Obtiene el valor formateado con la categoría
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->category) {
            return "{$this->value} ({$this->category->code})";
        }
        return $this->value;
    }

    /**
     * Scope para valores por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
