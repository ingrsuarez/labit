<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'cuit',
        'tax_condition',
        'address',
        'city',
        'state',
        'phone',
        'email',
        'iibb',
        'activity_start',
        'afip_cert_path',
        'afip_key_path',
        'afip_production',
        'is_active',
    ];

    protected $casts = [
        'activity_start' => 'date',
        'afip_production' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_default')->withTimestamps();
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }
}
