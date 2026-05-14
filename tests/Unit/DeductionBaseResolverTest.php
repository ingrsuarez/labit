<?php

namespace Tests\Unit;

use App\Services\Payroll\DeductionBaseResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeductionBaseResolverTest extends TestCase
{
    #[Test]
    public function usa_subtotal_remunerativo_por_defecto_y_ante_claves_invalidas(): void
    {
        $bases = ['basic' => 100_000.0, 'basic_antiguedad' => 150_000.0];
        $this->assertSame(500_000.0, DeductionBaseResolver::resolve(null, 500_000.0, 600_000.0, $bases));
        $this->assertSame(500_000.0, DeductionBaseResolver::resolve('custom', 500_000.0, 600_000.0, $bases));
    }

    #[Test]
    public function resuelve_total_haberes_distinto_del_remunerativo(): void
    {
        $bases = ['basic' => 100_000.0];
        $this->assertSame(550_000.0, DeductionBaseResolver::resolve('total_haberes', 400_000.0, 550_000.0, $bases));
        $this->assertSame(400_000.0, DeductionBaseResolver::resolve('subtotal_remunerativo', 400_000.0, 550_000.0, $bases));
    }

    #[Test]
    public function mapea_claves_del_array_bases(): void
    {
        $bases = [
            'basic' => 200_000.0,
            'basic_antiguedad' => 220_000.0,
            'basic_vacaciones' => 210_000.0,
            'basic_antiguedad_titulo' => 230_000.0,
            'basic_hours' => 205_000.0,
            'basic_hours_antiguedad' => 225_000.0,
        ];
        $this->assertSame(220_000.0, DeductionBaseResolver::resolve('basic_antiguedad', 1.0, 2.0, $bases));
    }
}
