<?php

namespace Tests\Feature;

use App\Services\BarcodeFormatService;
use Tests\TestCase;

class BarcodeFormatTest extends TestCase
{
    public function test_for_label_returns_protocol_with_material(): void
    {
        $this->assertSame(
            'C-2026-001234^EDTA',
            BarcodeFormatService::forLabel('C-2026-001234', 'EDTA')
        );
    }

    public function test_for_label_returns_protocol_only_when_material_is_null(): void
    {
        $this->assertSame(
            'C-2026-001234',
            BarcodeFormatService::forLabel('C-2026-001234', null)
        );
    }

    public function test_for_label_returns_protocol_only_when_material_is_empty_string(): void
    {
        $this->assertSame(
            'A-2026-005678',
            BarcodeFormatService::forLabel('A-2026-005678', '')
        );
    }

    public function test_for_label_trims_whitespace(): void
    {
        $this->assertSame(
            'V-2026-000012^ORI',
            BarcodeFormatService::forLabel('V-2026-000012', '  ORI  ')
        );

        $this->assertSame(
            'V-2026-000012',
            BarcodeFormatService::forLabel('V-2026-000012', '   ')
        );
    }

    public function test_supports_typical_material_abbreviations(): void
    {
        $cases = ['EDTA', 'SUE', 'ORI', 'CIT', 'HEP', 'FE', 'BV', 'HEM'];
        foreach ($cases as $abbr) {
            $this->assertSame(
                "C-2026-000001^{$abbr}",
                BarcodeFormatService::forLabel('C-2026-000001', $abbr)
            );
        }
    }
}
