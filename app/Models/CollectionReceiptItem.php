<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_receipt_id', 'sales_invoice_id', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function collectionReceipt() { return $this->belongsTo(CollectionReceipt::class); }
    public function invoice() { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
}
