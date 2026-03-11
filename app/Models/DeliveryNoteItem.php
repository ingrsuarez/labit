<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_note_id', 'supply_id', 'purchase_order_item_id',
        'quantity_received', 'notes',
    ];

    protected $casts = ['quantity_received' => 'decimal:2'];

    public function deliveryNote() { return $this->belongsTo(DeliveryNote::class); }
    public function supply() { return $this->belongsTo(Supply::class); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class); }
}
