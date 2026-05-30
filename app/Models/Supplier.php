<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'business_name',
        'tax_id',
        'tax_condition',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal',
        'cbu',
        'bank_alias',
        'bank_name',
        'contact_name',
        'contact_phone',
        'notes',
        'status',
        'is_fixed_expense',
    ];

    protected $casts = [
        'is_fixed_expense' => 'boolean',
    ];

    public function supplies()
    {
        return $this->hasMany(Supply::class, 'default_supplier_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    public function isActive(): bool
    {
        return $this->status === 'activo';
    }

    public function getTaxConditionLabelAttribute(): string
    {
        return match ($this->tax_condition) {
            'responsable_inscripto' => 'Responsable Inscripto',
            'monotributo' => 'Monotributo',
            'exento' => 'Exento',
            'consumidor_final' => 'Consumidor Final',
            default => '-',
        };
    }

    public static function generateCode(): string
    {
        // "PROV-" ocupa 5 caracteres; el índice 4 es el guión — substr(4) rompía el entero y siempre devolvía PROV-00001.
        $max = static::query()
            ->where('code', 'like', 'PROV-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, 5))
            ->max();

        return sprintf('PROV-%05d', ($max ?? 0) + 1);
    }
}
