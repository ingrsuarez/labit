<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultBatch extends Model
{
    protected $fillable = [
        'api_client_id', 'external_batch_id', 'source_app',
        'items_total', 'items_ingested', 'items_overwritten',
        'items_rejected', 'items_duplicate', 'raw_request',
    ];

    protected $casts = [
        'raw_request' => 'array',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function ingestions(): HasMany
    {
        return $this->hasMany(ResultIngestion::class);
    }
}
