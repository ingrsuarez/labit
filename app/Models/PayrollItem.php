<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'type',           // 'haber' o 'deduccion'
        'name',
        'percentage',
        'amount',
        'is_remunerative',
        'order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_remunerative' => 'boolean',
    ];

    /**
     * Relación con el recibo
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
