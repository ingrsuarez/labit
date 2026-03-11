<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'brand',
        'description',
        'supply_category_id',
        'unit',
        'stock',
        'min_stock',
        'last_price',
        'default_supplier_id',
        'is_active',
        'tracks_lot',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'last_price' => 'decimal:2',
        'is_active' => 'boolean',
        'tracks_lot' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SupplyCategory::class, 'supply_category_id');
    }

    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public static function generateCode(?int $categoryId = null): string
    {
        $prefix = 'INS';

        if ($categoryId) {
            $category = \App\Models\SupplyCategory::find($categoryId);
            if ($category && $category->code_prefix) {
                $prefix = strtoupper($category->code_prefix);
            }
        }

        $last = static::where('code', 'like', "{$prefix}-%")
            ->orderByDesc('code')
            ->first();

        if ($last) {
            $numPart = (int) substr($last->code, strlen($prefix) + 1);
            $nextNumber = $numPart + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%05d', $prefix, $nextNumber);
    }
}
