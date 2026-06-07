<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use Database\Seeders\NomencladorCamionerosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomencladorCamionerosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_nomenclador_camioneros(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorCamionerosSeeder::class);

        $nomenclador = Insurance::query()
            ->where('name', 'Nomenclador Camioneros')
            ->first();

        $this->assertNotNull($nomenclador);
        $this->assertSame('nomenclador', $nomenclador->type);
        $this->assertGreaterThanOrEqual(1200, InsuranceTest::query()->where('insurance_id', $nomenclador->id)->count());
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorCamionerosSeeder::class);
        $firstCount = InsuranceTest::query()->count();

        $this->seed(NomencladorCamionerosSeeder::class);

        $this->assertSame(1, Insurance::query()->where('name', 'Nomenclador Camioneros')->count());
        $this->assertSame($firstCount, InsuranceTest::query()->count());
    }

    public function test_seeder_actualiza_nbu_units_en_re_ejecucion(): void
    {
        $this->skipIfExcelMissing();

        $this->seed(NomencladorCamionerosSeeder::class);

        $nomenclador = Insurance::query()->where('name', 'Nomenclador Camioneros')->firstOrFail();
        $insuranceTest = InsuranceTest::query()->where('insurance_id', $nomenclador->id)->firstOrFail();
        $originalNbu = $insuranceTest->nbu_units;

        $insuranceTest->update(['nbu_units' => 999.99]);

        $this->seed(NomencladorCamionerosSeeder::class);

        $insuranceTest->refresh();
        $this->assertSame((float) $originalNbu, (float) $insuranceTest->nbu_units);
        $this->assertNotSame(999.99, (float) $insuranceTest->nbu_units);
    }

    private function skipIfExcelMissing(): void
    {
        if (! file_exists(base_path('docs/Nomenclador camioneros.xlsx'))) {
            $this->markTestSkipped('docs/Nomenclador camioneros.xlsx no presente en el entorno de test.');
        }
    }
}
