<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'number',
        'protocol_number',
        'patient_id',
        'room',
        'bed',
        'institution',
        'service',
        'applicant',
        'requesting_doctor',
        'invoice_date',
        'observations',
        'promise_date',
        'insurance',
        'affiliate_number',
        'diagnosis',
        'authorization_code',
        'attended_by',
        'insurance_price',
        'patient_price',
        'total_insurance',
        'total_patient',
        'total_copago',
        'cash',
        'created_by',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'invoice_date' => 'date',
        'promise_date' => 'date',
        'total_insurance' => 'decimal:2',
        'total_patient' => 'decimal:2',
        'total_copago' => 'decimal:2',
        'insurance_price' => 'float',
        'patient_price' => 'float',
    ];

    /**
     * Estados disponibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Genera el próximo número de protocolo
     */
    public static function generateProtocolNumber(): string
    {
        $year = date('Y');
        $lastAdmission = self::whereYear('date', $year)
            ->whereNotNull('protocol_number')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAdmission && $lastAdmission->protocol_number) {
            $parts = explode('-', $lastAdmission->protocol_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con el paciente
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con la obra social
     */
    public function insuranceRelation()
    {
        return $this->belongsTo(Insurance::class, 'insurance');
    }

    /**
     * Relación con las prácticas de la admisión
     */
    public function admissionTests()
    {
        return $this->hasMany(AdmissionTest::class);
    }

    /**
     * Relación con las prácticas (alias para compatibilidad)
     */
    public function tests()
    {
        return $this->belongsToMany(Test::class, 'admission_tests')
            ->withPivot(['price', 'nbu_units', 'authorization_status', 'paid_by_patient', 'copago', 'authorization_code', 'observations'])
            ->withTimestamps();
    }

    /**
     * Relación con el usuario que creó la admisión
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que atendió
     */
    public function attendedBy()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    /**
     * Calcula y actualiza los totales de la admisión
     */
    public function calculateTotals(): void
    {
        $totalInsurance = 0;
        $totalPatient = 0;
        $totalCopago = 0;

        foreach ($this->admissionTests as $test) {
            if ($test->paid_by_patient || $test->isRejected()) {
                $totalPatient += $test->price;
            } else {
                $totalInsurance += $test->price - $test->copago;
                $totalCopago += $test->copago;
            }
        }

        $this->update([
            'total_insurance' => $totalInsurance,
            'total_patient' => $totalPatient,
            'total_copago' => $totalCopago,
        ]);
    }

    /**
     * Obtiene el total general
     */
    public function getTotalAttribute(): float
    {
        return (float) $this->total_insurance + (float) $this->total_patient + (float) $this->total_copago;
    }

    /**
     * Obtiene la etiqueta del estado
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtiene la fecha formateada en español
     */
    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->date)->locale('es')->translatedFormat('d/m/Y');
    }

    /**
     * Scope para filtrar por obra social
     */
    public function scopeForInsurance($query, int $insuranceId)
    {
        return $query->where('insurance', $insuranceId);
    }

    /**
     * Scope para filtrar por mes y año
     */
    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    /**
     * Scope para admisiones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para admisiones completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
