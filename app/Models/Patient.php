<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lastName',
        'patientId',
        'email',
        'type',
        'sex',
        'birth',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'insurance',
        'insurance_cod',
        'status',
    ];

    protected $casts = [
        'birth' => 'date',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Obtiene el nombre completo del paciente
     */
    public function getFullNameAttribute(): string
    {
        return ucfirst($this->name) . ' ' . ucfirst($this->lastName);
    }

    /**
     * Relación con la obra social del paciente
     */
    public function insuranceRelation()
    {
        return $this->belongsTo(Insurance::class, 'insurance');
    }

    /**
     * Relación con las admisiones del paciente
     */
    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * Scope para buscar pacientes por DNI o nombre
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('patientId', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('lastName', 'like', "%{$search}%");
        });
    }
}
