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
    
    /**
     * Calcular días hábiles de una licencia que corresponden a un mes específico
     * Útil cuando las vacaciones cruzan de un mes a otro
     */
    public static function calculateWorkingDaysForMonth($startDate, $endDate, int $year, int $month): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // Límites del mes
        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $monthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        
        // Ajustar el rango a los límites del mes
        $rangeStart = $start->lt($monthStart) ? $monthStart : $start;
        $rangeEnd = $end->gt($monthEnd) ? $monthEnd : $end;
        
        // Si el rango no está dentro del mes, devolver 0
        if ($rangeStart->gt($rangeEnd)) {
            return 0;
        }
        
        return self::calculateWorkingDays($rangeStart, $rangeEnd);
    }
}






