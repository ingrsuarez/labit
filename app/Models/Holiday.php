<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Verificar si una fecha es feriado
     */
    public static function isHoliday($date): bool
    {
        return self::where('date', $date)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtener todos los feriados de un año
     */
    public static function getByYear(int $year): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereYear('date', $year)
            ->where('is_active', true)
            ->orderBy('date')
            ->get();
    }

    /**
     * Obtener feriados entre dos fechas
     */
    public static function getBetween($startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->orderBy('date')
            ->get();
    }
}
