<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;

class WorkingDaysService
{
    /**
     * Calcular días hábiles entre dos fechas (excluyendo fines de semana y feriados)
     */
    public static function calculateWorkingDays(Carbon|string $start, Carbon|string $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        $workingDays = 0;
        $holidays = self::getHolidaysDates($start, $end);

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            // Excluir sábados (6) y domingos (0)
            if ($date->isWeekend()) {
                continue;
            }

            // Excluir feriados
            if (in_array($date->format('Y-m-d'), $holidays)) {
                continue;
            }

            $workingDays++;
        }

        return $workingDays;
    }

    /**
     * Obtener detalle de días entre dos fechas
     */
    public static function getDaysDetail(Carbon|string $start, Carbon|string $end): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($start->gt($end)) {
            return ['total' => 0, 'working' => 0, 'weekends' => 0, 'holidays' => 0];
        }

        $total = 0;
        $working = 0;
        $weekends = 0;
        $holidayCount = 0;

        $holidays = self::getHolidaysDates($start, $end);
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $total++;

            if ($date->isWeekend()) {
                $weekends++;
                continue;
            }

            if (in_array($date->format('Y-m-d'), $holidays)) {
                $holidayCount++;
                continue;
            }

            $working++;
        }

        return [
            'total' => $total,
            'working' => $working,
            'weekends' => $weekends,
            'holidays' => $holidayCount,
        ];
    }

    /**
     * Obtener array de fechas de feriados en un rango
     */
    private static function getHolidaysDates(Carbon $start, Carbon $end): array
    {
        // Si la tabla holidays tiene un campo 'date', usarlo
        // Por ahora retornamos array vacío hasta que se complete la estructura
        try {
            if (\Schema::hasColumn('holidays', 'date')) {
                return Holiday::whereBetween('date', [$start, $end])
                    ->pluck('date')
                    ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Si hay error, continuar sin feriados
        }

        return [];
    }

    /**
     * Verificar si una fecha es día hábil
     */
    public static function isWorkingDay(Carbon|string $date): bool
    {
        $date = Carbon::parse($date);

        if ($date->isWeekend()) {
            return false;
        }

        $holidays = self::getHolidaysDates($date, $date);

        return !in_array($date->format('Y-m-d'), $holidays);
    }
}

