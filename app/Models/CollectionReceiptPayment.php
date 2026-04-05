<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionReceiptPayment extends Model
{
    protected $fillable = [
        'collection_receipt_id',
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
