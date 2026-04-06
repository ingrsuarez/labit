<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionReceiptPayment extends Model
{
    protected $fillable = [
        'collection_receipt_id',
        'payment_order_id',
        'line_type',
        'amount',
        'bank_account_id',
        'cheque_number',
        'bank_name',
        'due_date',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function collectionReceipt(): BelongsTo
    {
        return $this->belongsTo(CollectionReceipt::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    /**
     * E-cheq en cartera: RC confirmado, misma empresa, aún no asignado a una OP.
     */
    public function scopeAvailableInPortfolio(Builder $query, int $companyId): Builder
    {
        return $query
            ->where('line_type', 'echeq')
            ->whereNull('payment_order_id')
            ->whereHas('collectionReceipt', function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId)->where('status', 'confirmado');
            });
    }

    public function getLineTypeLabelAttribute(): string
    {
        return match ($this->line_type) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'echeq' => 'E-cheq',
            default => $this->line_type,
        };
    }
}
