<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id', 'supply_id', 'purchase_service_id', 'description',
        'quantity', 'unit_price', 'iva_rate', 'iva_amount', 'total',
        'lot_number', 'expiration_date', 'updates_stock',
    ];

    protected $casts = [
        'quantity' => 'decimal:2', 'unit_price' => 'decimal:2',
        'iva_rate' => 'decimal:2', 'iva_amount' => 'decimal:2', 'total' => 'decimal:2',
        'expiration_date' => 'date',
        'updates_stock' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function purchaseService()
    {
        return $this->belongsTo(PurchaseService::class, 'purchase_service_id');
    }
}
