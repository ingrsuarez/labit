<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

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
        'liquidated_at',
        'paid_at',
        'notes',
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

    // Relaciones
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class)->orderBy('type')->orderBy('order');
    }

    public function haberes()
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'haber')->orderBy('order');
    }

    public function deducciones()
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'deduccion')->orderBy('order');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeLiquidado($query)
    {
        return $query->whereIn('status', ['liquidado', 'pagado']);
    }

    public function scopePagado($query)
    {
        return $query->where('status', 'pagado');
    }

    // Helpers
    public function isLiquidado(): bool
    {
        return in_array($this->status, ['liquidado', 'pagado']);
    }

    public function isPagado(): bool
    {
        return $this->status === 'pagado';
    }

    public function canEdit(): bool
    {
        return $this->status === 'borrador';
    }
}
