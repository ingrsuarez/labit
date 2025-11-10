<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFile extends Model
{
     protected $fillable = [
        'document_id',
        'filename',
        'display_name',
        'mime_type',
        'size',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
