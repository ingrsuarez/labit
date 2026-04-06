<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionReceiptWithholding extends Model
{
    public const TYPE_GANANCIAS = 'ganancias';

    public const TYPE_IVA = 'iva';

    public const TYPE_SUSS_931 = 'suss_931';

    public const TYPE_IIBB = 'iibb';

    public const TYPES = [
        self::TYPE_GANANCIAS,
        self::TYPE_IVA,
        self::TYPE_SUSS_931,
        self::TYPE_IIBB,
    ];

    protected $fillable = [
        'collection_receipt_id',
        'withholding_type',
        'document_number',
        'regime',
        'jurisdiction',
        'certificate_number',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function collectionReceipt(): BelongsTo
    {
        return $this->belongsTo(CollectionReceipt::class);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_GANANCIAS => 'Ganancias',
            self::TYPE_IVA => 'IVA',
            self::TYPE_SUSS_931 => 'SUSS (Ley 19.640 / 931)',
            self::TYPE_IIBB => 'IIBB',
            default => $type,
        };
    }
}
