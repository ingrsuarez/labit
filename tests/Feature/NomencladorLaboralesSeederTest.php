<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use Database\Seeders\NomencladorLaboralesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomencladorLaboralesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_nomenclador_laborales(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorLaboralesSeeder::class);

        $nomenclador = Insurance::query()
            ->where('name', 'Nomenclador laborales')
            ->first();

        $this->assertNotNull($nomenclador);
        $this->assertSame('nomenclador', $nomenclador->type);
        $this->assertGreaterThanOrEqual(80, InsuranceTest::query()->where('insurance_id', $nomenclador->id)->count());
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorLaboralesSeeder::class);
        $firstCount = InsuranceTest::query()->count();

        $this->seed(NomencladorLaboralesSeeder::class);

        $this->assertSame(1, Insurance::query()->where('name', 'Nomenclador laborales')->count());
        $this->assertSame($firstCount, InsuranceTest::query()->count());
    }

    public function test_seeder_actualiza_nbu_units_en_re_ejecucion(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorLaboralesSeeder::class);

        $nomenclador = Insurance::query()->where('name', 'Nomenclador laborales')->firstOrFail();
        $insuranceTest = InsuranceTest::query()->where('insurance_id', $nomenclador->id)->firstOrFail();
        $originalNbu = $insuranceTest->nbu_units;

        $insuranceTest->update(['nbu_units' => 999.99]);

        $this->seed(NomencladorLaboralesSeeder::class);

        $insuranceTest->refresh();
        $this->assertSame((float) $originalNbu, (float) $insuranceTest->nbu_units);
        $this->assertNotSame(999.99, (float) $insuranceTest->nbu_units);
    }

    private function skipIfExcelMissing(): void
    {
        if (! file_exists(base_path('docs/Nomenclador laborales.xlsx'))) {
            $this->markTestSkipped('docs/Nomenclador laborales.xlsx no presente en el entorno de test.');
        }
    }
}
