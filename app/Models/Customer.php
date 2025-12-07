<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'taxId',
        'tax',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal',
        'status',
    ];

    /**
     * Relación con las muestras
     */
    public function samples()
    {
        return $this->hasMany(Sample::class);
    }

    /**
     * Verifica si el cliente está activo
     */
    public function isActive(): bool
    {
        return $this->status === 'activo';
    }
}
