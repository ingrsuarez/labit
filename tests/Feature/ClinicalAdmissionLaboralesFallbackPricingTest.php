<?php

namespace Tests\Feature;

use App\Enums\DeterminationProfileLabType;
use App\Models\DeterminationProfile;
use App\Models\Insurance;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClinicalAdmissionLaboralesFallbackPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
        Permission::findOrCreate('lab-admissions.create');
    }

    public function test_preview_admission_usa_nbu_de_determinacion_para_laborales_sin_nomenclador(): void
    {
        $this->assertClinicalFallbackPricingForInsurance([
            'name' => 'Empresa laboral sin nomenclador',
            'type' => 'laborales',
            'nbu_value' => 100,
            'nomenclator_id' => null,
        ]);
    }

    public function test_preview_admission_usa_nbu_de_determinacion_para_cobertura_sin_nomenclador_asignado(): void
    {
        $this->assertClinicalFallbackPricingForInsurance([
            'name' => 'Cobertura sin nomenclador',
            'type' => 'obra_social',
            'nbu_value' => 100,
            'nomenclator_id' => null,
        ]);
    }

    private function assertClinicalFallbackPricingForInsurance(array $insuranceData): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-admissions.create']);

        $insurance = Insurance::query()->create($insuranceData);

        $test = Test::query()->create([
            'code' => 'LABNBU1',
            'name' => 'Determinacion laboral NBU',
            'nbu' => 2.5,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $profile = DeterminationProfile::query()->create([
            'name' => 'Perfil laboral',
            'lab_type' => DeterminationProfileLabType::Clinico,
            'is_active' => true,
        ]);
        $profile->tests()->attach($test->id, ['sort_order' => 0]);

        $response = $this->actingAs($user)->postJson(route('determination-profiles.preview.admission'), [
            'insurance_id' => $insurance->id,
            'profile_ids' => [$profile->id],
            'existing_test_ids' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('tests_added_count', 1);
        $response->assertJsonPath('skipped_not_in_nomenclator', []);
        $response->assertJsonPath('admission_rows.0.id', $test->id);
        $response->assertJsonPath('admission_rows.0.calculated_price', 250);
    }
}
