<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code_prefix',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
