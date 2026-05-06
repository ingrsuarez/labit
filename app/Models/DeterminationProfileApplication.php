<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeterminationProfileApplication extends Model
{
    protected $fillable = [
        'applicable_type',
        'applicable_id',
        'user_id',
        'profiles_snapshot',
        'tests_added_count',
        'tests_skipped_duplicate_count',
        'skipped_details',
    ];

    protected function casts(): array
    {
        return [
            'profiles_snapshot' => 'array',
            'skipped_details' => 'array',
        ];
    }

    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
