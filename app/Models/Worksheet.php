<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worksheet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'created_by'];

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'worksheet_test')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
