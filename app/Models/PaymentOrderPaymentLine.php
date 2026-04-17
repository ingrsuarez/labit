<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrderPaymentLine extends Model
{
    protected $fillable = [
        'payment_order_id',
        'sort_order',
        'kind',
        'amount',
        'bank_account_id',
        'collection_receipt_payment_id',
        'payment_reference',
        'cheque_due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_due_date' => 'date',
    ];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function collectionReceiptPayment(): BelongsTo
    {
        return $this->belongsTo(CollectionReceiptPayment::class);
    }

    public function getKindLabelAttribute(): string
    {
        return match ($this->kind) {
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'efectivo' => 'Efectivo',
            'portfolio_echeq' => 'E-cheq cartera (terceros)',
            default => $this->kind,
        };
    }
}
