<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'type',
        'name',
        'percentage',
        'amount',
        'is_remunerative',
        'order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_remunerative' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Liquidación a la que pertenece
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
