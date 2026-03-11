<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointOfSale extends Model
{
    use HasFactory;

    protected $table = 'points_of_sale';

    protected $fillable = [
        'code',
        'name',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }
}
