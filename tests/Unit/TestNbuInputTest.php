<?php

namespace Tests\Unit;

use App\Support\TestNbuInput;
use PHPUnit\Framework\TestCase;

class TestNbuInputTest extends TestCase
{
    public function test_normalizes_trailing_zero_decimal(): void
    {
        $this->assertSame('1.5', TestNbuInput::normalize('1.50'));
    }

    public function test_normalizes_comma_decimal_separator(): void
    {
        $this->assertSame('1.5', TestNbuInput::normalize('1,5'));
    }

    public function test_normalizes_integer(): void
    {
        $this->assertSame('1', TestNbuInput::normalize('1'));
    }

    public function test_empty_returns_null(): void
    {
        $this->assertNull(TestNbuInput::normalize(''));
        $this->assertNull(TestNbuInput::normalize(null));
    }
}
