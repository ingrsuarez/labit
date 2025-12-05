<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

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
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
