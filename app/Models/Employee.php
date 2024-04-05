<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Job;

class Employee extends Model
{
    use HasFactory;

    public function jobs()
    {
        return $this->belongsToMany('App\Models\Job','job_employee','employee_id', 'job_id')->withTimestamps();
    }

    public function leaves()
    {
        return $this->hasMany('App\Models\Leave','employee_id');
    }

}
