<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkingDaysService
{
    /**
     * Calcular días hábiles entre dos fechas (excluye sábados, domingos y feriados)
     */
    public static function calculateWorkingDays($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        if ($start->gt($end)) {
            return 0;
        }
        
        // Obtener feriados en el rango
        $holidays = Holiday::getBetween($start, $end)->pluck('date')->map(function($date) {
            return $date->format('Y-m-d');
        })->toArray();
        
        $workingDays = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            // Excluir sábados (6) y domingos (0)
            if (!$current->isWeekend()) {
                // Excluir feriados
                if (!in_array($current->format('Y-m-d'), $holidays)) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Verificar si una fecha es día hábil
     */
    public static function isWorkingDay($date): bool
    {
        $date = Carbon::parse($date);
        
        // No es día hábil si es fin de semana
        if ($date->isWeekend()) {
            return false;
        }
        
        // No es día hábil si es feriado
        if (Holiday::isHoliday($date)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener el detalle de días entre dos fechas
     */
    public static function getDaysDetail($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $holidays = Holiday::getBetween($start, $end)->pluck('date')->map(function($date) {
            return $date->format('Y-m-d');
        })->toArray();
        
        $workingDays = 0;
        $weekendDays = 0;
        $holidayDays = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            if ($current->isWeekend()) {
                $weekendDays++;
            } elseif (in_array($current->format('Y-m-d'), $holidays)) {
                $holidayDays++;
            } else {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return [
            'total' => $start->diffInDays($end) + 1,
            'working' => $workingDays,
            'weekends' => $weekendDays,
            'holidays' => $holidayDays,
        ];
    }
}



