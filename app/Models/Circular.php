<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'created_by',
        'date',
        'status',
        'sector',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Sectores disponibles
     */
    public static function sectors(): array
    {
        return [
            'laboratorio' => 'Laboratorio',
            'administracion' => 'Administración',
            'calidad' => 'Calidad',
            'produccion' => 'Producción',
            'mantenimiento' => 'Mantenimiento',
            'rrhh' => 'Recursos Humanos',
            'general' => 'General (Todos)',
        ];
    }

    /**
     * Estados posibles
     */
    public static function statuses(): array
    {
        return [
            'activa' => 'Activa',
            'inactiva' => 'Inactiva',
        ];
    }

    /**
     * Usuario creador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Genera el próximo código de circular
     */
    public static function generateCode(): string
    {
        $year = date('Y');
        $lastCircular = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCircular && preg_match('/CIR-' . $year . '-(\d+)/', $lastCircular->code, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('CIR-%s-%03d', $year, $nextNumber);
    }

    /**
     * Color de badge según estado
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'activa' => 'bg-green-100 text-green-800',
            'inactiva' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope para circulares activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'activa');
    }

    /**
     * Scope para búsqueda por descripción
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Empleados que han firmado esta circular
     */
    public function signatures()
    {
        return $this->hasMany(CircularSignature::class);
    }

    /**
     * Empleados que han firmado (relación many-to-many a través de signatures)
     */
    public function signedEmployees()
    {
        return $this->belongsToMany(Employee::class, 'circular_signatures')
            ->withPivot(['read_at', 'signed_at', 'ip_address'])
            ->withTimestamps();
    }

    /**
     * Verificar si un empleado ha firmado esta circular
     */
    public function isSignedBy(Employee $employee): bool
    {
        return $this->signatures()
            ->where('employee_id', $employee->id)
            ->whereNotNull('signed_at')
            ->exists();
    }

    /**
     * Verificar si un empleado ha leído esta circular
     */
    public function isReadBy(Employee $employee): bool
    {
        return $this->signatures()
            ->where('employee_id', $employee->id)
            ->whereNotNull('read_at')
            ->exists();
    }

    /**
     * Obtener circulares pendientes de firma para un empleado
     */
    public static function pendingForEmployee(Employee $employee)
    {
        return self::active()
            ->whereDoesntHave('signatures', function($query) use ($employee) {
                $query->where('employee_id', $employee->id)
                      ->whereNotNull('signed_at');
            })
            ->orderBy('date', 'desc');
    }
}
