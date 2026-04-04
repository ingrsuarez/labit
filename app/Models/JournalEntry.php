<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'date', 'number', 'description',
        'source_type', 'source_id', 'is_automatic', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_automatic' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isBalanced(): bool
    {
        return abs($this->lines->sum('debit') - $this->lines->sum('credit')) < 0.01;
    }

    public static function nextNumber(int $companyId, int $year): int
    {
        $max = self::where('company_id', $companyId)
            ->whereYear('date', $year)
            ->max('number');

        return ($max ?? 0) + 1;
    }

    public static function deleteForSource(object $source): void
    {
        self::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->delete();
    }
}
