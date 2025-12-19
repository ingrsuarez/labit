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
}
