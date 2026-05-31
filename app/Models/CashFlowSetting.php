<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowSetting extends Model
{
    protected $fillable = [
        'company_id',
        'iva_due_day',
        'form931_due_day',
    ];

    protected $casts = [
        'iva_due_day' => 'integer',
        'form931_due_day' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function forCompany(int $companyId): self
    {
        if ($companyId <= 0 || ! Company::whereKey($companyId)->exists()) {
            throw new \InvalidArgumentException('Empresa inválida para configuración de flujo de caja.');
        }

        return self::query()->firstOrCreate(
            ['company_id' => $companyId],
            ['iva_due_day' => 20, 'form931_due_day' => 9]
        );
    }
}
