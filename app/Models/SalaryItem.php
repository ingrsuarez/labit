<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation_type',
        'calculation_base', // 'basic', 'basic_antiguedad', 'basic_hours', 'basic_hours_antiguedad', 'subtotal'
        'value',
        'base',
        'is_remunerative',
        'is_active',
        'requires_assignment', // Si requiere asignación individual a empleados
        'applies_all_year',
        'recurrent_month',
        'specific_month',
        'specific_year',
        'order',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_remunerative' => 'boolean',
        'is_active' => 'boolean',
        'requires_assignment' => 'boolean',
        'applies_all_year' => 'boolean',
    ];

    /**
     * Empleados que tienen asignado este concepto
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_salary_item')
                    ->withPivot('is_active', 'custom_value')
                    ->withTimestamps();
    }

    /**
     * Scope para obtener solo haberes
     */
    public function scopeHaberes($query)
    {
        return $query->where('type', 'haber');
    }

    /**
     * Scope para obtener solo deducciones
     */
    public function scopeDeducciones($query)
    {
        return $query->where('type', 'deduccion');
    }

    /**
     * Scope para obtener solo activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar conceptos que aplican en un período específico
     * 
     * Un concepto aplica si:
     * - applies_all_year = true (aplica siempre), O
     * - recurrent_month = mes del período (se repite cada año), O
     * - specific_month = mes Y specific_year = año (período puntual)
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where(function($q) use ($month, $year) {
            // Aplica todo el año
            $q->where('applies_all_year', true)
              // O es un mes recurrente (cada año)
              ->orWhere('recurrent_month', $month)
              // O es un período específico
              ->orWhere(function($q2) use ($month, $year) {
                  $q2->where('specific_month', $month)
                     ->where('specific_year', $year);
              });
        });
    }

    /**
     * Obtener descripción del período de aplicación
     */
    public function getPeriodDescriptionAttribute(): string
    {
        if ($this->applies_all_year) {
            return 'Todo el año';
        }
        
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        if ($this->recurrent_month) {
            return 'Cada ' . $months[$this->recurrent_month];
        }
        
        if ($this->specific_month && $this->specific_year) {
            return $months[$this->specific_month] . ' ' . $this->specific_year;
        }
        
        return 'Sin período definido';
    }

    /**
     * Obtener el tipo formateado
     */
    public function getTypeNameAttribute()
    {
        return $this->type === 'haber' ? 'Haber' : 'Deducción';
    }

    /**
     * Obtener el tipo de cálculo formateado
     */
    public function getCalculationTypeNameAttribute()
    {
        return match($this->calculation_type) {
            'percentage' => 'Porcentaje',
            'fixed' => 'Monto Fijo',
            'hours' => 'Por Horas',
            default => $this->calculation_type,
        };
    }

    /**
     * Calcular el monto basado en un salario base
     */
    public function calculate($baseSalary, $hours = 0)
    {
        return match($this->calculation_type) {
            'percentage' => $baseSalary * ($this->value / 100),
            'fixed' => $this->value,
            'hours' => $hours * $this->value,
            default => 0,
        };
    }
}
