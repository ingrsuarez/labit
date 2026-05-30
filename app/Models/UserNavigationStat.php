<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNavigationStat extends Model
{
    protected $fillable = [
        'user_id',
        'shortcut_key',
        'hit_count',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
