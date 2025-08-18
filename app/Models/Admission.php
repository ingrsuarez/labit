<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'number', 'room', 'bed', 'institution', 'service', 'applicant', 
        'invoice_date', 'observations', 'promise_date', 'insurance', 'diagnosis', 
        'authorization_code', 'attended_by', 'insurance_price', 'patient_price', 
        'cash', 'created_by', 'status'
    ];

    // Relación con los análisis
    public function analyses()
    {
        return $this->hasMany(AdmissionAnalysis::class);
    }
}
