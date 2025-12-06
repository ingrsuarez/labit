<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;

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

    /**
     * Usuario asociado al empleado
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jobs()
    {
        return $this->belongsToMany(\App\Models\Job::class, 'job_employee', 'employee_id', 'job_id')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    /**
     * Verificar si el empleado es supervisor (tiene puestos con subordinados)
     */
    public function isSupervisor(): bool
    {
        foreach ($this->jobs as $job) {
            if ($job->childs()->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener todos los empleados subordinados (directos e indirectos)
     */
    public function getSubordinates(): \Illuminate\Support\Collection
    {
        $subordinates = collect();
        
        foreach ($this->jobs as $job) {
            $subordinates = $subordinates->merge($this->getSubordinatesFromJob($job));
        }
        
        return $subordinates->unique('id');
    }

    /**
     * Obtener empleados subordinados de un puesto específico (recursivo)
     */
    protected function getSubordinatesFromJob(Job $job): \Illuminate\Support\Collection
    {
        $subordinates = collect();
        
        foreach ($job->childs as $childJob) {
            // Agregar empleados del puesto hijo
            $subordinates = $subordinates->merge($childJob->employees);
            
            // Recursivamente obtener subordinados de puestos inferiores
            $subordinates = $subordinates->merge($this->getSubordinatesFromJob($childJob));
        }
        
        return $subordinates;
    }

    /**
     * Obtener nombre completo del empleado
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->lastName}";
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

    /**
     * Calcular años de antigüedad del empleado
     */
    public function getAntiquityYearsAttribute(): int
    {
        if (!$this->start_date) {
            return 0;
        }
        return Carbon::parse($this->start_date)->diffInYears(now());
    }

    /**
     * Calcular días de vacaciones según ley argentina (Art. 150 LCT)
     * - Hasta 5 años: 14 días corridos
     * - De 5 a 10 años: 21 días corridos
     * - De 10 a 20 años: 28 días corridos
     * - Más de 20 años: 35 días corridos
     */
    public function getVacationDaysByLawAttribute(): int
    {
        $years = $this->antiquity_years;

        if ($years < 5) {
            return 14;
        } elseif ($years < 10) {
            return 21;
        } elseif ($years < 20) {
            return 28;
        } else {
            return 35;
        }
    }

    /**
     * Días de vacaciones usados en un año específico (días hábiles)
     */
    public function getUsedVacationDays(int $year = null): int
    {
        $year = $year ?? now()->year;

        $leaves = $this->leaves()
            ->where('type', 'vacaciones')
            ->where('status', 'aprobado')
            ->whereYear('start', $year)
            ->get();

        return $leaves->sum(function ($leave) {
            return $leave->working_days;
        });
    }

    /**
     * Días de vacaciones pendientes (solicitadas pero no aprobadas) - días hábiles
     */
    public function getPendingVacationDays(int $year = null): int
    {
        $year = $year ?? now()->year;

        $leaves = $this->leaves()
            ->where('type', 'vacaciones')
            ->where('status', 'pendiente')
            ->whereYear('start', $year)
            ->get();

        return $leaves->sum(function ($leave) {
            return $leave->working_days;
        });
    }

    /**
     * Días de vacaciones disponibles en un año
     */
    public function getAvailableVacationDays(int $year = null): int
    {
        $year = $year ?? now()->year;
        $totalDays = $this->vacation_days_by_law;
        $usedDays = $this->getUsedVacationDays($year);

        return max(0, $totalDays - $usedDays);
    }

    /**
     * Resumen completo de vacaciones del año
     */
    public function getVacationSummary(int $year = null): array
    {
        $year = $year ?? now()->year;

        return [
            'year' => $year,
            'total_by_law' => $this->vacation_days_by_law,
            'used' => $this->getUsedVacationDays($year),
            'pending' => $this->getPendingVacationDays($year),
            'available' => $this->getAvailableVacationDays($year),
            'antiquity_years' => $this->antiquity_years,
        ];
    }

}
