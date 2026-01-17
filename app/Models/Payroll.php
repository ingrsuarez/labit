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
        'created_by',
        'approved_by',
        'approved_at',
        'liquidated_at',
        'paid_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'approved_at' => 'datetime',
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
     * Scope para filtrar por período (año y mes)
     */
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Verificar si la liquidación está cerrada (liquidada o pagada)
     */
    public function isLiquidado(): bool
    {
        return in_array($this->status, ['liquidado', 'pagado']);
    }

    /**
     * Verificar si la liquidación está pagada
     */
    public function isPagado(): bool
    {
        return $this->status === 'pagado';
    }

    /**
     * Relación con el empleado
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relación con los items de la liquidación
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Items de tipo haber
     */
    public function haberes(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'haber');
    }

    /**
     * Items de tipo deducción
     */
    public function deducciones(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'deduccion');
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
}
