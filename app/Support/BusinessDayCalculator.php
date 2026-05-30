<?php

namespace App\Support;

use App\Services\WorkingDaysService;
use Carbon\Carbon;

class BusinessDayCalculator
{
    public static function nthBusinessDayOfMonth(int $year, int $month, int $n): Carbon
    {
        $current = Carbon::create($year, $month, 1)->startOfDay();
        $lastDay = $current->copy()->endOfMonth();
        $count = 0;

        while ($current->lte($lastDay)) {
            if (WorkingDaysService::isWorkingDay($current)) {
                $count++;
                if ($count >= $n) {
                    return $current->copy();
                }
            }
            $current->addDay();
        }

        return $lastDay->copy();
    }

    public static function dueDateOnDayOfMonth(int $year, int $month, int $day): Carbon
    {
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->day;
        $safeDay = min(max($day, 1), $lastDay);

        return Carbon::create($year, $month, $safeDay)->startOfDay();
    }
}
