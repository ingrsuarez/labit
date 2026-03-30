<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceProtocol extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'protocol_type',
        'protocol_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function protocol()
    {
        return $this->morphTo();
    }
}
