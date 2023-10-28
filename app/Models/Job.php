<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class Job extends Model
{
    use HasFactory;

    public function employees()
    {
        return $this->belongsToMany('App\Models\Employee','job_employee','employee_id', 'job_id');
    }
}
