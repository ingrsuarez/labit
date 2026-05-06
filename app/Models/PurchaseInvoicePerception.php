<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoicePerception extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'purchase_perception_id',
        'accounting_account_id',
        'name_snapshot',
        'jurisdiction_snapshot',
        'rate_snapshot',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate_snapshot' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function perception(): BelongsTo
    {
        return $this->belongsTo(PurchasePerception::class, 'purchase_perception_id');
    }

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }
}
