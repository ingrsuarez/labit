<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'level', 'is_header', 'is_active',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeImputable($query)
    {
        return $query->where('is_header', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function hasMovements(): bool
    {
        return $this->journalEntryLines()->exists();
    }

    public function getBalanceAttribute(): float
    {
        $debitSum = $this->journalEntryLines()->sum('debit');
        $creditSum = $this->journalEntryLines()->sum('credit');

        if (in_array($this->type, ['activo', 'resultado_negativo'])) {
            return $debitSum - $creditSum;
        }

        return $creditSum - $debitSum;
    }
}
