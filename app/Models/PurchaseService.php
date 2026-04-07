<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseService extends Model
{
    protected $fillable = [
        'company_id',
        'purchase_service_category_id',
        'code',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PurchaseServiceCategory::class, 'purchase_service_category_id');
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_service_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    /**
     * Grupos para el selector en facturas de compra (categorías con al menos un servicio activo).
     *
     * @return list<array{id: int|null, name: string, services: list<array{id: int, code: string|null, name: string}>}>
     */
    public static function catalogGroupsForCompany(int $companyId): array
    {
        $groups = [];

        $categories = PurchaseServiceCategory::query()
            ->forCompany($companyId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['services' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
            ->get();

        foreach ($categories as $cat) {
            if ($cat->services->isEmpty()) {
                continue;
            }
            $groups[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'services' => $cat->services->map(fn (self $s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                ])->values()->all(),
            ];
        }

        $uncat = self::query()
            ->forCompany($companyId)
            ->active()
            ->whereNull('purchase_service_category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($uncat->isNotEmpty()) {
            $groups[] = [
                'id' => null,
                'name' => 'Sin categoría',
                'services' => $uncat->map(fn (self $s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                ])->values()->all(),
            ];
        }

        return $groups;
    }
}
