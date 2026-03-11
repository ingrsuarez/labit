<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id', 'description', 'test_id',
        'quantity', 'unit_price', 'iva_rate', 'iva_amount', 'total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2', 'unit_price' => 'decimal:2',
        'iva_rate' => 'decimal:2', 'iva_amount' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function test() { return $this->belongsTo(Test::class); }
}
