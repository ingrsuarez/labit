<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSpeciesReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'species_id',
        'low',
        'high',
        'other_reference',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }

    public function getFormattedRangeAttribute(): string
    {
        if ($this->other_reference) {
            if ($this->low || $this->high) {
                return ($this->low ?? '').' - '.($this->high ?? '').' | '.$this->other_reference;
            }

            return $this->other_reference;
        }

        if ($this->low && $this->high) {
            return $this->low.' - '.$this->high;
        }

        if ($this->low) {
            return '>= '.$this->low;
        }

        if ($this->high) {
            return '<= '.$this->high;
        }

        return '';
    }
}
