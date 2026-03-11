<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_quotation_request_id',
        'supply_id',
        'quantity',
        'unit_price',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function quotationRequest()
    {
        return $this->belongsTo(PurchaseQuotationRequest::class, 'purchase_quotation_request_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function getTotalAttribute(): float
    {
        if ($this->unit_price === null) return 0;
        return round($this->quantity * $this->unit_price, 2);
    }
}
