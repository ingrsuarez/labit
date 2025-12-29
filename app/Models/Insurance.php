<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_id',
        'tax',
        'address',
        'phone',
        'email',
        'state',
        'country',
        'price',
        'nbu',
        'nbu_value',
        'group',
        'type',
        'instructions',
    ];

    protected $casts = [
        'nbu_value' => 'decimal:2',
        'price' => 'float',
        'nbu' => 'float',
    ];

    /**
     * Relación con el nomenclador de prácticas
     */
    public function nomenclator()
    {
        return $this->hasMany(InsuranceTest::class);
    }

    /**
     * Relación con las prácticas a través del nomenclador
     */
    public function tests()
    {
        return $this->belongsToMany(Test::class, 'insurance_tests')
            ->withPivot(['nbu_units', 'price', 'requires_authorization', 'copago', 'observations'])
            ->withTimestamps();
    }

    /**
     * Relación con las admisiones
     */
    public function admissions()
    {
        return $this->hasMany(Admission::class, 'insurance');
    }

    /**
     * Relación con los pacientes
     */
    public function patients()
    {
        return $this->hasMany(Patient::class, 'insurance');
    }

    /**
     * Relación con el grupo
     */
    public function groupRelation()
    {
        return $this->belongsTo(Group::class, 'group');
    }

    /**
     * Obtiene el precio de una práctica específica para esta obra social
     */
    public function getTestPrice(int $testId): ?float
    {
        $insuranceTest = $this->nomenclator()->where('test_id', $testId)->first();
        return $insuranceTest?->price;
    }

    /**
     * Calcula el precio de una práctica basado en NBU
     */
    public function calculateTestPrice(Test $test): float
    {
        $insuranceTest = $this->nomenclator()->where('test_id', $test->id)->first();
        
        if ($insuranceTest && $insuranceTest->price) {
            return $insuranceTest->price;
        }

        // Calcular basado en NBU si no tiene precio fijo
        $nbuUnits = $insuranceTest?->nbu_units ?? $test->nbu ?? 1;
        $nbuValue = $this->nbu_value ?? 0;
        
        return $nbuUnits * $nbuValue;
    }
}
