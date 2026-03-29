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
        'type',
        'discount_percent',
        'afip_activity',
        'cuit_status',
        'afip_verified_at',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'afip_verified_at' => 'datetime',
        'type' => 'array',
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

    public function isAfipVerified(): bool
    {
        return $this->afip_verified_at !== null;
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->type ?? []);
    }

    public function isVeterinary(): bool
    {
        return $this->hasType('veterinario');
    }

    public function veterinarians()
    {
        return $this->hasMany(Veterinarian::class);
    }
}
