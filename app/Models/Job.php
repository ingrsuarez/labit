<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class Job extends Model
{
    use HasFactory;
    
    protected $fillable = ['name','parent_id','department','agreement','category_id','responsibilities','email'];

    public function employees()
    {
        return $this->belongsToMany('App\Models\Employee','job_employee', 'job_id','employee_id')->withTimestamps();
    }

    /**
     * Puestos hijos (subordinados directos)
     */
    public function childs() {
        return $this->hasMany('App\Models\Job','parent_id','id') ;
    }

    /**
     * Puesto padre (supervisor directo)
     */
    public function parent()
    {
        return $this->belongsTo(Job::class, 'parent_id');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category','category_id')->withDefault();
    }

    /**
     * Verificar si este puesto tiene subordinados
     */
    public function hasSubordinates(): bool
    {
        return $this->childs()->exists();
    }

    /**
     * Obtener todos los empleados subordinados (de puestos hijos)
     */
    public function getSubordinateEmployees(): \Illuminate\Support\Collection
    {
        $subordinates = collect();
        
        foreach ($this->childs as $childJob) {
            $subordinates = $subordinates->merge($childJob->employees);
            // Recursivamente obtener subordinados de niveles inferiores
            $subordinates = $subordinates->merge($childJob->getSubordinateEmployees());
        }
        
        return $subordinates->unique('id');
    }

    /**
     * Obtener supervisor del puesto (empleado del puesto padre)
     */
    public function getSupervisor(): ?Employee
    {
        if (!$this->parent) {
            return null;
        }
        
        return $this->parent->employees->first();
    }

}
