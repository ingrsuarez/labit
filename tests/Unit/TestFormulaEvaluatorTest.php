<?php

namespace Tests\Unit;

use App\Models\Test;
use App\Support\TestFormulaEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestFormulaEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private TestFormulaEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new TestFormulaEvaluator;
    }

    private function makeTest(array $overrides = []): Test
    {
        return Test::query()->create(array_merge([
            'code' => 'T'.uniqid(),
            'name' => 'test',
            'decimals' => 2,
            'price' => 0,
            'cost' => 0,
            'formula' => null,
            'categories' => ['clinico'],
        ], $overrides));
    }

    public function test_evaluates_castelli_style_division(): void
    {
        $col = $this->makeTest(['code' => 'COL-T']);
        $hdl = $this->makeTest(['code' => 'HDL']);
        $castelli = $this->makeTest([
            'code' => 'CAST',
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $col->id],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $hdl->id],
                ],
            ],
        ]);

        $result = $this->evaluator->evaluateForTest($castelli, [
            $col->id => '200',
            $hdl->id => '50',
        ]);

        $this->assertSame('4.00', $result);
    }

    public function test_returns_null_when_operand_missing(): void
    {
        $col = $this->makeTest();
        $hdl = $this->makeTest();
        $castelli = $this->makeTest([
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $col->id],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $hdl->id],
                ],
            ],
        ]);

        $this->assertNull($this->evaluator->evaluateForTest($castelli, [
            $col->id => '100',
        ]));
    }

    public function test_returns_null_on_division_by_zero(): void
    {
        $a = $this->makeTest();
        $b = $this->makeTest();
        $calc = $this->makeTest([
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $a->id],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $b->id],
                ],
            ],
        ]);

        $this->assertNull($this->evaluator->evaluateForTest($calc, [
            $a->id => '10',
            $b->id => '0',
        ]));
    }

    public function test_zero_operand_is_valid(): void
    {
        $a = $this->makeTest();
        $b = $this->makeTest();
        $calc = $this->makeTest([
            'decimals' => 1,
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $a->id],
                    ['type' => 'op', 'value' => '+'],
                    ['type' => 'test', 'test_id' => $b->id],
                ],
            ],
        ]);

        $this->assertSame('5.0', $this->evaluator->evaluateForTest($calc, [
            $a->id => '0',
            $b->id => '5',
        ]));
    }

    public function test_parentheses_and_comma_decimal(): void
    {
        $a = $this->makeTest();
        $b = $this->makeTest();
        $calc = $this->makeTest([
            'formula' => [
                'tokens' => [
                    ['type' => 'paren', 'value' => '('],
                    ['type' => 'test', 'test_id' => $a->id],
                    ['type' => 'op', 'value' => '+'],
                    ['type' => 'test', 'test_id' => $b->id],
                    ['type' => 'paren', 'value' => ')'],
                    ['type' => 'op', 'value' => '/'],
                    ['type' => 'test', 'test_id' => $b->id],
                ],
            ],
        ]);

        $result = $this->evaluator->evaluateForTest($calc, [
            $a->id => '1,5',
            $b->id => '2',
        ]);

        $this->assertSame('1.75', $result);
    }

    public function test_evaluates_with_numeric_constant(): void
    {
        $glucose = $this->makeTest(['code' => 'GLU']);
        $calc = $this->makeTest([
            'decimals' => 2,
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $glucose->id],
                    ['type' => 'op', 'value' => '*'],
                    ['type' => 'number', 'value' => '0.0556'],
                ],
            ],
        ]);

        $result = $this->evaluator->evaluateForTest($calc, [
            $glucose->id => '100',
        ]);

        $this->assertSame('5.56', $result);
    }

    public function test_evaluates_subtraction_with_constant(): void
    {
        $total = $this->makeTest();
        $calc = $this->makeTest([
            'formula' => [
                'tokens' => [
                    ['type' => 'test', 'test_id' => $total->id],
                    ['type' => 'op', 'value' => '-'],
                    ['type' => 'number', 'value' => '5'],
                ],
            ],
        ]);

        $this->assertSame('195.00', $this->evaluator->evaluateForTest($calc, [
            $total->id => '200',
        ]));
    }
}
