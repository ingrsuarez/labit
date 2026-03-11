<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_order_id', 'purchase_invoice_id', 'amount',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function paymentOrder() { return $this->belongsTo(PaymentOrder::class); }
    public function invoice() { return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); }
}
