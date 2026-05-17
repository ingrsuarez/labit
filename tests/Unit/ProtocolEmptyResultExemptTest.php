<?php

namespace Tests\Unit;

use App\Models\Test;
use App\Models\VetAdmissionTest;
use App\Support\ProtocolEmptyResultExempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolEmptyResultExemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_exempt_and_empty_when_flag_set_and_no_result(): void
    {
        $test = $this->makeExemptTest();

        $row = new VetAdmissionTest([
            'test_id' => $test->id,
            'result' => null,
            'is_validated' => false,
        ]);
        $row->setRelation('test', $test);

        $this->assertTrue(ProtocolEmptyResultExempt::isExemptAndEmpty($row));
    }

    public function test_not_exempt_when_result_present(): void
    {
        $test = $this->makeExemptTest();

        $row = new VetAdmissionTest([
            'test_id' => $test->id,
            'result' => 'segmentado 70%',
            'is_validated' => false,
        ]);
        $row->setRelation('test', $test);

        $this->assertFalse(ProtocolEmptyResultExempt::isExemptAndEmpty($row));
    }

    private function makeExemptTest(): Test
    {
        return Test::query()->create([
            'code' => 'EX-FL',
            'name' => 'formula leucocitaria',
            'unit' => null,
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => null,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => 0,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['veterinario'],
            'sort_order' => 0,
            'empty_result_exempt' => true,
        ]);
    }
}
