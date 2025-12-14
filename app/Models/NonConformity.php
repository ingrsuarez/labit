<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonConformity extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'employee_id',
        'reported_by',
        'date',
        'type',
        'severity',
        'description',
        'procedure_name',
        'training_name',
        'corrective_action',
        'preventive_action',
        'status',
        'closed_at',
        'closed_by',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * Tipos de no conformidad
     */
    public static function types(): array
    {
        return [
            'procedimiento' => 'Incumplimiento de Procedimiento',
            'capacitacion' => 'Falta de Capacitación',
            'seguridad' => 'Seguridad e Higiene',
            'calidad' => 'Calidad',
            'otro' => 'Otro',
        ];
    }

    /**
     * Niveles de severidad
     */
    public static function severities(): array
    {
        return [
            'leve' => 'Leve',
            'moderada' => 'Moderada',
            'grave' => 'Grave',
        ];
    }

    /**
     * Estados posibles
     */
    public static function statuses(): array
    {
        return [
            'abierta' => 'Abierta',
            'en_proceso' => 'En Proceso',
            'cerrada' => 'Cerrada',
        ];
    }

    /**
     * Relación con empleado
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Usuario que reporta
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Usuario que cierra
     */
    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Seguimientos
     */
    public function followUps()
    {
        return $this->hasMany(NonConformityFollowUp::class)->orderBy('created_at', 'desc');
    }

    /**
     * Genera el próximo código de NC
     */
    public static function generateCode(): string
    {
        $year = date('Y');
        $lastNC = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastNC && preg_match('/NC-' . $year . '-(\d+)/', $lastNC->code, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('NC-%s-%03d', $year, $nextNumber);
    }

    /**
     * Color de badge según severidad
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'leve' => 'bg-yellow-100 text-yellow-800',
            'moderada' => 'bg-orange-100 text-orange-800',
            'grave' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Color de badge según estado
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'abierta' => 'bg-red-100 text-red-800',
            'en_proceso' => 'bg-blue-100 text-blue-800',
            'cerrada' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope para NCs abiertas
     */
    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'cerrada');
    }

    /**
     * Scope para NCs cerradas
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'cerrada');
    }
}
