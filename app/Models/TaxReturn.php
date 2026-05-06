<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tax_id',
        'period_year',
        'period_month',
        'declared_amount',
        'applied_total',
        'balance',
        'status',
        'notes',
        'journal_entry_id',
        'cancellation_journal_entry_id',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'declared_amount' => 'decimal:2',
        'applied_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TaxReturnApplication::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function cancellationJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'cancellation_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function recalculateTotals(): void
    {
        $sum = (float) $this->applications()->sum('amount_applied');
        $this->applied_total = round($sum, 2);
        $this->balance = round((float) $this->declared_amount - $this->applied_total, 2);
    }

    public function getPeriodLabelAttribute(): string
    {
        $tax = $this->relationLoaded('tax') ? $this->tax : $this->tax()->first();
        $frequency = $tax?->frequency ?? 'monthly';

        return match ($frequency) {
            'monthly' => sprintf('%02d/%d', (int) ($this->period_month ?? 1), (int) $this->period_year),
            'quarterly' => sprintf('%d-Q%d', (int) $this->period_year, (int) ceil(($this->period_month ?? 1) / 3)),
            'annual' => (string) (int) $this->period_year,
            default => (string) $this->period_year,
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function scopeForPeriod($query, int $taxId, int $year, ?int $month)
    {
        return $query->where('tax_id', $taxId)
            ->where('period_year', $year)
            ->where('period_month', $month);
    }
}
