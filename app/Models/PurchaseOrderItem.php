<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'supply_id', 'quantity', 'received_quantity',
        'unit_price', 'total', 'notes', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2', 'received_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function supply() { return $this->belongsTo(Supply::class); }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
