<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'jurisdiction',
        'liability_account_id',
        'frequency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'liability_account_id');
    }

    /** Percepciones de compra asociadas a este impuesto. */
    public function perceptions(): HasMany
    {
        return $this->hasMany(PurchasePerception::class);
    }

    public function taxReturns(): HasMany
    {
        return $this->hasMany(TaxReturn::class);
    }
}
