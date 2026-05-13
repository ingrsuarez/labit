<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\GeneratesProtocolNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use Auditable, GeneratesProtocolNumber, HasFactory;

    protected $fillable = [
        'date',
        'number',
        'protocol_number',
        'external_equipment_sample_id',
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
        'lab_branch_id',
        'status',
        'sent_at',
        'payment_status',
        'payment_method',
        'paid_amount',
        'payment_date',
        'payment_notes',
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
        'paid_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Estados disponibles
     */
    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_VALIDATED = 'validated';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Genera el próximo número de protocolo
     */
    public static function generateProtocolNumber(): string
    {
        return static::generatePrefixedProtocolNumber('C');
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

    public function determinationProfileApplications()
    {
        return $this->morphMany(DeterminationProfileApplication::class, 'applicable')->latest();
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

    public function labBranch()
    {
        return $this->belongsTo(LabBranch::class);
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

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * Obtiene la etiqueta del estado
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->sent_at !== null && $this->status === self::STATUS_VALIDATED) {
            return 'Enviado';
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_VALIDATED => 'Validado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Obtiene el color del badge del estado
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->sent_at !== null && $this->status === self::STATUS_VALIDATED) {
            return 'sky';
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_VALIDATED => 'purple',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Estado calculado a partir del estado real de las prácticas.
     * Requiere que admissionTests esté cargado (eager load).
     */
    public function getCalculatedStatusAttribute(): string
    {
        $countable = $this->admissionTests->filter(function (AdmissionTest $at) {
            if ($at->hasResult() || $at->is_validated) {
                return true;
            }
            if (! $at->relationLoaded('test') || ! $at->test) {
                return true;
            }
            $t = $at->test;
            if ($t->relationLoaded('childTests') && $t->childTests->isNotEmpty()) {
                return false;
            }
            if ($t->relationLoaded('children') && $t->children->isNotEmpty()) {
                return false;
            }

            return true;
        });

        $total = $countable->count();

        if ($total === 0) {
            return self::STATUS_PENDING;
        }

        $validated = $countable->where('is_validated', true)->count();
        $withResult = $countable->filter(fn ($at) => $at->hasResult())->count();

        if ($validated === $total) {
            return self::STATUS_VALIDATED;
        }

        if ($withResult === $total || $validated > 0) {
            return self::STATUS_COMPLETED;
        }

        if ($withResult > 0) {
            return self::STATUS_IN_PROGRESS;
        }

        return self::STATUS_PENDING;
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

    public function isParticular(): bool
    {
        return $this->insuranceRelation?->type === 'particular';
    }

    public function getBalanceAttribute(): float
    {
        $total = (float) ($this->total_patient ?: ($this->patient_price ?: 0));

        return max(0, $total - (float) ($this->paid_amount ?? 0));
    }

    public function getTotalToPayAttribute(): float
    {
        return (float) ($this->total_patient ?: ($this->patient_price ?: 0));
    }

    public function scopeDebtors($query)
    {
        return $query->whereIn('payment_status', ['pendiente', 'parcial']);
    }

    public function invoiceProtocols()
    {
        return $this->morphMany(InvoiceProtocol::class, 'protocol');
    }

    public function salesInvoices()
    {
        return $this->morphToMany(SalesInvoice::class, 'protocol', 'invoice_protocols')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function isInvoiced(): bool
    {
        return $this->invoiceProtocols()->exists();
    }

    public function scopeUninvoiced($query)
    {
        return $query->whereDoesntHave('invoiceProtocols');
    }

    public function scopeInvoiced($query)
    {
        return $query->whereHas('invoiceProtocols');
    }
}
