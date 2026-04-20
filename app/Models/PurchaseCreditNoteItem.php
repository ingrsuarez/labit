<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseCreditNoteItem extends Model
{
    protected $fillable = [
        'purchase_credit_note_id', 'supply_id', 'purchase_service_id', 'description',
        'quantity', 'unit_price', 'iva_rate', 'iva_amount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'iva_rate' => 'decimal:2',
        'iva_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseCreditNote(): BelongsTo
    {
        return $this->belongsTo(PurchaseCreditNote::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function purchaseService(): BelongsTo
    {
        return $this->belongsTo(PurchaseService::class, 'purchase_service_id');
    }
}
