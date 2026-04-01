<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id', 'period_from', 'period_to',
        'opening_balance', 'closing_balance',
        'total_credits', 'total_debits', 'movements_count',
        'filename', 'imported_by', 'imported_at', 'status',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'total_debits' => 'decimal:2',
        'imported_at' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BankMovement::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function getReconciliationProgressAttribute(): array
    {
        $total = $this->movements()->count();
        if ($total === 0) {
            return ['total' => 0, 'matched' => 0, 'pending' => 0, 'ignored' => 0, 'percent' => 0];
        }

        $matched = $this->movements()->where('reconciliation_status', 'matched')->count();
        $ignored = $this->movements()->where('reconciliation_status', 'ignored')->count();
        $pending = $total - $matched - $ignored;

        return [
            'total' => $total,
            'matched' => $matched,
            'pending' => $pending,
            'ignored' => $ignored,
            'percent' => $total > 0 ? round(($matched + $ignored) / $total * 100) : 0,
        ];
    }
}
