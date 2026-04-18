<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultIngestion extends Model
{
    protected $fillable = [
        'result_batch_id', 'api_client_id',
        'external_message_id', 'hl7_control_id', 'protocol_number',
        'protocol_type', 'equipment_name',
        'status', 'items_summary', 'rejection_reason',
    ];

    protected $casts = [
        'items_summary' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ResultBatch::class, 'result_batch_id');
    }
}
