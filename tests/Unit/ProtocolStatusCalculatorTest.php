<?php

namespace Tests\Unit;

use App\Services\ProtocolStatusCalculator;
use PHPUnit\Framework\TestCase;

class ProtocolStatusCalculatorTest extends TestCase
{
    private ProtocolStatusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProtocolStatusCalculator;
    }

    public function test_pending_when_all_empty(): void
    {
        $items = [
            $this->item(false, false),
            $this->item(false, false),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_PENDING, $this->calculator->calculate($items));
    }

    public function test_pending_when_no_items(): void
    {
        $this->assertSame(ProtocolStatusCalculator::STATUS_PENDING, $this->calculator->calculate([]));
    }

    public function test_in_progress_when_mixed_loaded_and_empty(): void
    {
        $items = [
            $this->item(true, false),
            $this->item(false, false),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_IN_PROGRESS, $this->calculator->calculate($items));
    }

    public function test_completed_when_all_loaded_none_validated(): void
    {
        $items = [
            $this->item(true, false),
            $this->item(true, false),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_COMPLETED, $this->calculator->calculate($items));
    }

    public function test_partially_validated_when_some_validated_rest_loaded(): void
    {
        $items = [
            $this->item(true, true),
            $this->item(true, false),
            $this->item(true, false),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_PARTIALLY_VALIDATED, $this->calculator->calculate($items));
    }

    public function test_in_progress_when_partial_validation_with_empty_slot(): void
    {
        $items = [
            $this->item(true, true),
            $this->item(true, false),
            $this->item(false, false),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_IN_PROGRESS, $this->calculator->calculate($items));
    }

    public function test_validated_when_all_validated(): void
    {
        $items = [
            $this->item(true, true),
            $this->item(true, true),
        ];

        $this->assertSame(ProtocolStatusCalculator::STATUS_VALIDATED, $this->calculator->calculate($items));
    }

    public function test_is_sent_helper(): void
    {
        $this->assertFalse(ProtocolStatusCalculator::isSent(null));
        $this->assertTrue(ProtocolStatusCalculator::isSent(new \DateTimeImmutable));
    }

    private function item(bool $hasResult, bool $isValidated): object
    {
        return new class($hasResult, $isValidated)
        {
            public function __construct(
                private bool $hasResult,
                public bool $is_validated,
            ) {}

            public function hasResult(): bool
            {
                return $this->hasResult || $this->is_validated;
            }
        };
    }
}
