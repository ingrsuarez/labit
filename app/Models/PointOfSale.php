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
        'is_electronic',
        'afip_pos_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_electronic' => 'boolean',
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
