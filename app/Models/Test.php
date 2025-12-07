<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'low',
        'high',
        'instructions',
        'parent',
        'decimals',
        'negative',
        'positive',
        'questions',
        'method',
        'price',
        'cost',
        'work_sheet',
        'material',
        'formula',
        'box',
        'nbu',
    ];

    /**
     * Relación con determinaciones de muestras
     */
    public function sampleDeterminations()
    {
        return $this->hasMany(SampleDetermination::class);
    }

    /**
     * Relación con el test padre
     */
    public function parentTest()
    {
        return $this->belongsTo(Test::class, 'parent');
    }

    /**
     * Relación con tests hijos
     */
    public function children()
    {
        return $this->hasMany(Test::class, 'parent');
    }

    /**
     * Obtiene el nombre del material
     */
    public function getMaterialNameAttribute(): string
    {
        $materials = [
            1 => 'EDTA',
            2 => 'Suero',
            3 => 'Orina',
            4 => 'Citrato',
            5 => 'Heparina',
        ];

        return $materials[$this->material] ?? 'N/A';
    }
}
