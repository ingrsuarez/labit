<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'agreement',
        'union_name',
        'wage',
        'base_weekly_hours',
        'full_time',
    ];

    protected $casts = [
        'wage' => 'decimal:2',
        'base_weekly_hours' => 'integer',
    ];

    public function jobs()
    {
        return $this->hasMany('App\Models\Job');
    }
}
