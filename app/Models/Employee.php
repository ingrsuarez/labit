<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Job;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lastName',
        'employeeId',
        'user_id',
        'email',
        'start_date',
        'vacation_days',
        'bank_account',
        'position',
        'health_registration',
        'sex',
        'weekly_hours',
        'birth',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'status',
    ];

    public function jobs()
    {
        return $this->belongsToMany(\App\Models\Job::class, 'job_employee', 'employee_id', 'job_id')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function leaves()
    {
        return $this->hasMany('App\Models\Leave','employee_id');
    }

    /**
     * Conceptos de sueldo asignados a este empleado
     */
    public function salaryItems()
    {
        return $this->belongsToMany(SalaryItem::class, 'employee_salary_item')
                    ->withPivot('is_active', 'custom_value')
                    ->withTimestamps();
    }

    /**
     * Verificar si el empleado tiene asignado un concepto
     */
    public function hasSalaryItem(int $salaryItemId): bool
    {
        return $this->salaryItems()
                    ->where('salary_item_id', $salaryItemId)
                    ->where('employee_salary_item.is_active', true)
                    ->exists();
    }

}
