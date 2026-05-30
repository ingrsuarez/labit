<?php

namespace Tests\Unit;

use App\Models\Holiday;
use App\Support\BusinessDayCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDayCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_nth_business_day_of_may_2026(): void
    {
        // May 2026: 1=Friday, 5th business day should be May 7 (Thu? let's verify)
        // Fri 1, Mon 4, Tue 5, Wed 6, Thu 7 = 5th business day if no holidays
        $date = BusinessDayCalculator::nthBusinessDayOfMonth(2026, 5, 5);

        $this->assertSame(5, $date->month);
        $this->assertTrue($date->isWeekday());
    }

    public function test_due_date_clamps_to_last_day_of_month(): void
    {
        $date = BusinessDayCalculator::dueDateOnDayOfMonth(2026, 2, 31);
        $this->assertSame(28, $date->day);
    }

    public function test_skips_holiday_for_business_day(): void
    {
        Holiday::query()->create([
            'date' => '2026-06-01',
            'name' => 'Feriado test',
            'type' => 'fijo',
        ]);

        $first = BusinessDayCalculator::nthBusinessDayOfMonth(2026, 6, 1);
        $this->assertNotSame('2026-06-01', $first->toDateString());
    }
}
