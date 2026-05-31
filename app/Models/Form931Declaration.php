<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Form931Declaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'period_year',
        'period_month',
        'amount_aportes_patronales',
        'amount_contribuciones_patronales',
        'total',
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
        'amount_aportes_patronales' => 'decimal:2',
        'amount_contribuciones_patronales' => 'decimal:2',
        'total' => 'decimal:2',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function recalculateTotal(): void
    {
        $this->total = round(
            (float) $this->amount_aportes_patronales + (float) $this->amount_contribuciones_patronales,
            2
        );
    }

    public function getPeriodLabelAttribute(): string
    {
        return sprintf('%02d/%d', (int) $this->period_month, (int) $this->period_year);
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

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
