<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'doctor',
        'start',
        'end',
        'hour_50',
        'hour_100',
        'description',
        'file',
        'user_id',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee','employee_id');
    }
}
