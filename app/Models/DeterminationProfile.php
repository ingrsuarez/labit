<?php

namespace App\Models;

use App\Enums\DeterminationProfileLabType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeterminationProfile extends Model
{
    protected $fillable = [
        'name',
        'lab_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'lab_type' => DeterminationProfileLabType::class,
        ];
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'determination_profile_test')
            ->withPivot('sort_order')
            ->orderBy('determination_profile_test.sort_order')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLabType($query, DeterminationProfileLabType|string $labType)
    {
        $value = $labType instanceof DeterminationProfileLabType ? $labType->value : $labType;

        return $query->where('lab_type', $value);
    }
}
