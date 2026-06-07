<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityEmail extends Model
{
    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'email',
        'label',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
}
