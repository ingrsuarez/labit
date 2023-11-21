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
        return $this->belongsToMany('App\Models\Employee','job_employee', 'job_id','employee_id')->withTimestamps();
    }

    public function childs() {
        return $this->hasMany('App\Models\Job','parent_id','id') ;
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category','category_id')->withDefault();
    }
}
