<?php

namespace Tests\Unit;

use App\Models\AdmissionTest;
use App\Models\Test;
use App\Services\BillingSummaryCodeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingSummaryCodeResolverTest extends TestCase
{
    use RefreshDatabase;

    private BillingSummaryCodeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new BillingSummaryCodeResolver;
    }

    public function test_parent_and_children_in_protocol_only_parent_code_included(): void
    {
        $parent = $this->createTest('PARENT01');
        $child = $this->createTest('CHILD01');
        $parent->childTests()->attach($child->id, ['order' => 1]);

        $parentLine = $this->line($parent->id, 100);
        $childLine = $this->line($child->id, 50);

        $result = $this->resolver->resolve(
            collect([$parentLine, $childLine]),
            fn ($l) => $l->test,
            fn ($l) => (int) $l->test_id,
            fn () => true,
            fn ($l) => (float) $l->price,
        );

        $this->assertSame('PARENT01', $result['codes_string']);
        $this->assertEquals(100.0, $result['total_amount']);
        $this->assertCount(1, $result['included']);
    }

    public function test_orphan_child_is_included_when_parent_not_in_protocol(): void
    {
        $parent = $this->createTest('PARENT02');
        $child = $this->createTest('CHILD02');
        $parent->childTests()->attach($child->id, ['order' => 1]);

        $childLine = $this->line($child->id, 75);

        $result = $this->resolver->resolve(
            collect([$childLine]),
            fn ($l) => $l->test,
            fn ($l) => (int) $l->test_id,
            fn () => true,
            fn ($l) => (float) $l->price,
        );

        $this->assertSame('CHILD02', $result['codes_string']);
        $this->assertEquals(75.0, $result['total_amount']);
    }

    public function test_two_standalone_tests_joined_with_dash(): void
    {
        $a = $this->createTest('AAA');
        $b = $this->createTest('BBB');

        $result = $this->resolver->resolve(
            collect([$this->line($a->id, 10), $this->line($b->id, 20)]),
            fn ($l) => $l->test,
            fn ($l) => (int) $l->test_id,
            fn () => true,
            fn ($l) => (float) $l->price,
        );

        $this->assertSame('AAA-BBB', $result['codes_string']);
        $this->assertEquals(30.0, $result['total_amount']);
    }

    public function test_clinical_paid_by_patient_line_excluded(): void
    {
        $test = $this->createTest('OS100');

        $included = $this->line($test->id, 80, paidByPatient: false);
        $excluded = $this->line($test->id, 40, paidByPatient: true);

        $result = $this->resolver->resolve(
            collect([$included, $excluded]),
            fn ($l) => $l->test,
            fn ($l) => (int) $l->test_id,
            fn (AdmissionTest $at) => ! $at->paid_by_patient
                && $at->authorization_status !== AdmissionTest::STATUS_REJECTED,
            fn (AdmissionTest $at) => (float) $at->price - (float) $at->copago,
        );

        $this->assertSame('OS100', $result['codes_string']);
        $this->assertEquals(80.0, $result['total_amount']);
    }

    private function createTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Test '.$code,
            'unit' => 'mg/dL',
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
            'categories' => ['lab'],
            'sort_order' => 0,
            'empty_result_exempt' => false,
        ]);
    }

    private function line(int $testId, float $price, bool $paidByPatient = false): AdmissionTest
    {
        $test = Test::query()->with('parentTests')->find($testId);

        $line = new AdmissionTest([
            'test_id' => $testId,
            'price' => $price,
            'copago' => 0,
            'paid_by_patient' => $paidByPatient,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);
        $line->id = random_int(1, 99999);
        $line->setRelation('test', $test);

        return $line;
    }
}
