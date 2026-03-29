<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabBranch extends Model
{
    protected $fillable = [
        'name', 'address', 'city', 'province', 'zip_code',
        'phone', 'email', 'is_central', 'is_active',
    ];

    protected $casts = [
        'is_central' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class, 'lab_branch_id');
    }

    public function samples()
    {
        return $this->hasMany(Sample::class, 'lab_branch_id');
    }

    public function vetAdmissions()
    {
        return $this->hasMany(VetAdmission::class, 'lab_branch_id');
    }
}
