<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Holiday;

class WorkingDaysService
{
    /**
     * Calcular días hábiles entre dos fechas (excluyendo fines de semana y feriados)
     */
    public static function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        $workingDays = 0;
        $current = $start->copy();
        
        // Obtener feriados del período
        $holidays = self::getHolidaysInRange($start, $end);
        
        while ($current->lte($end)) {
            // Si no es fin de semana y no es feriado, es día hábil
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Obtener detalle de días entre dos fechas
     */
    public static function getDaysDetail(Carbon $start, Carbon $end): array
    {
        $total = 0;
        $working = 0;
        $weekends = 0;
        $holidayCount = 0;
        
        $current = $start->copy();
        $holidays = self::getHolidaysInRange($start, $end);
        
        while ($current->lte($end)) {
            $total++;
            
            if ($current->isWeekend()) {
                $weekends++;
            } elseif (in_array($current->format('Y-m-d'), $holidays)) {
                $holidayCount++;
            } else {
                $working++;
            }
            
            $current->addDay();
        }
        
        return [
            'total' => $total,
            'working' => $working,
            'weekends' => $weekends,
            'holidays' => $holidayCount,
        ];
    }

    /**
     * Obtener feriados en un rango de fechas
     */
    private static function getHolidaysInRange(Carbon $start, Carbon $end): array
    {
        try {
            return Holiday::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->pluck('date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();
        } catch (\Exception $e) {
            // Si la tabla holidays no existe o hay error, devolver array vacío
            return [];
        }
    }
}
