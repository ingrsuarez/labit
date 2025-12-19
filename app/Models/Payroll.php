<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'period_label',
        'employee_name',
        'employee_cuil',
        'category_name',
        'position_name',
        'antiguedad_years',
        'start_date',
        'salario_basico',
        'total_haberes',
        'total_remunerativo',
        'total_no_remunerativo',
        'total_deducciones',
        'neto_a_cobrar',
        'status',
        'liquidated_at',
        'paid_at',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'liquidated_at' => 'datetime',
        'paid_at' => 'datetime',
        'salario_basico' => 'decimal:2',
        'total_haberes' => 'decimal:2',
        'total_remunerativo' => 'decimal:2',
        'total_no_remunerativo' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'neto_a_cobrar' => 'decimal:2',
    ];

    /**
     * Empleado asociado
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Items de la liquidación (haberes y deducciones)
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->orderBy('order');
    }

    /**
     * Solo haberes
     */
    public function haberes(): HasMany
    {
        return $this->hasMany(PayrollItem::class)
            ->where('type', 'haber')
            ->orderBy('order');
    }

    /**
     * Solo deducciones
     */
    public function deducciones(): HasMany
    {
        return $this->hasMany(PayrollItem::class)
            ->where('type', 'deduccion')
            ->orderBy('order');
    }

    /**
     * Usuario que creó la liquidación
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que aprobó la liquidación
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope para filtrar por período (año y mes)
     */
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Verificar si está liquidado
     */
    public function isLiquidado(): bool
    {
        return in_array($this->status, ['liquidado', 'pagado']);
    }

    /**
     * Verificar si está pagado
     */
    public function isPagado(): bool
    {
        return $this->status === 'pagado';
    }
}
