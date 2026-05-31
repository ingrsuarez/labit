<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowObligation extends Model
{
    public const CATEGORY_GANANCIAS = 'ganancias_pago';

    public const CATEGORY_ARCA = 'arca_plan_pago';

    public const CATEGORY_ECHEQ = 'echeq_emitido';

    public const CATEGORY_CUOTA_EQUIPO = 'cuota_equipo';

    public const CATEGORIES = [
        self::CATEGORY_GANANCIAS,
        self::CATEGORY_ARCA,
        self::CATEGORY_ECHEQ,
        self::CATEGORY_CUOTA_EQUIPO,
    ];

    protected $fillable = [
        'company_id',
        'category',
        'title',
        'amount',
        'due_date',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calendarCategory(): string
    {
        return match ($this->category) {
            self::CATEGORY_GANANCIAS => 'impuesto_ganancias',
            self::CATEGORY_ARCA => 'arca_plan',
            self::CATEGORY_ECHEQ => 'echeq_emitido',
            self::CATEGORY_CUOTA_EQUIPO => 'cuota_equipo',
            default => $this->category,
        };
    }

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_GANANCIAS => 'Pago Ganancias',
            self::CATEGORY_ARCA => 'Plan de pago ARCA',
            self::CATEGORY_ECHEQ => 'E-cheq emitido',
            self::CATEGORY_CUOTA_EQUIPO => 'Cuota de equipo',
        ];
    }
}
