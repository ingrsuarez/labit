<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest as VetAdmissionTestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * v1.76.0 — Indicador "Ratificado" por determinación.
 * Verifica persistencia y reglas de negocio en los tres módulos
 * (laboratorio clínico, veterinario y muestras aguas/alimentos).
 */
class RatifiedDeterminationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createTest(string $code = 'RAT1', string $name = 'Determinación ratificable'): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => $name,
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
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }
        $user = User::factory()->create();
        foreach ($permissions as $perm) {
            $user->givePermissionTo($perm);
        }

        return $user;
    }

    private function makePatient(string $name, string $lastName, string $patientId): Patient
    {
        return Patient::query()->create([
            'name' => $name,
            'lastName' => $lastName,
            'patientId' => $patientId,
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);
    }

    private function makeAdmission(Patient $patient, User $user, string $protocolNumber, int $number): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => $protocolNumber,
            'number' => (string) $number,
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_lab_save_results_persiste_is_ratified_para_usuario_con_permiso_validar(): void
    {
        $user = $this->userWithPermissions(['lab.section', 'lab-results.create', 'lab-results.validate']);
        $patient = $this->makePatient('Juan', 'Pérez', '12345678');
        $admission = $this->makeAdmission($patient, $user, 'C-2026-RAT001', 1001);
        $test = $this->createTest('LABRAT1');
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $response = $this->actingAs($user)->post(route('lab.admissions.saveResults', $admission), [
            'results' => [
                [
                    'id' => $at->id,
                    'result' => '8.5',
                    'unit' => 'mg/dL',
                    'reference_value' => '3 - 5',
                    'is_ratified' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();
        $at->refresh();
        $this->assertTrue($at->is_ratified);
        $this->assertNotNull($at->ratified_at);
        $this->assertSame($user->id, $at->ratified_by);
    }

    public function test_lab_save_results_ignora_is_ratified_si_usuario_no_puede_validar(): void
    {
        $user = $this->userWithPermissions(['lab.section', 'lab-results.create']);
        $patient = $this->makePatient('Ana', 'Gómez', '99887766');
        $admission = $this->makeAdmission($patient, $user, 'C-2026-RAT002', 1002);
        $test = $this->createTest('LABRAT2');
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)->post(route('lab.admissions.saveResults', $admission), [
            'results' => [
                [
                    'id' => $at->id,
                    'result' => '8.5',
                    'unit' => 'mg/dL',
                    'reference_value' => '3 - 5',
                    'is_ratified' => '1',
                ],
            ],
        ]);

        $at->refresh();
        $this->assertFalse((bool) $at->is_ratified);
        $this->assertNull($at->ratified_at);
        $this->assertNull($at->ratified_by);
    }

    public function test_lab_unvalidate_limpia_ratificacion(): void
    {
        $user = $this->userWithPermissions(['lab.section', 'lab-results.validate']);
        $patient = $this->makePatient('María', 'López', '12349876');
        $admission = $this->makeAdmission($patient, $user, 'C-2026-RAT003', 1003);
        $test = $this->createTest('LABRAT3');
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'result' => '7.2',
            'is_validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'is_ratified' => true,
            'ratified_at' => now(),
            'ratified_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('lab.admissions.unvalidateTest', [$admission, $at]));

        $at->refresh();
        $this->assertFalse($at->is_validated);
        $this->assertFalse($at->is_ratified);
        $this->assertNull($at->ratified_at);
        $this->assertNull($at->ratified_by);
    }

    public function test_vet_load_results_persiste_is_ratified(): void
    {
        Permission::findOrCreate('lab.section');
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $customer = Customer::query()->create([
            'name' => 'Veterinaria Ratif',
            'taxId' => '30-99999990-1',
            'status' => 'activo',
            'type' => ['veterinario'],
            'veterinary_nbu_value' => 100,
        ]);
        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'CAN',
            'is_active' => true,
        ]);
        $vet = VetAdmission::query()->create([
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Pedro Dueño',
            'protocol_number' => 'V-2026-RAT001',
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'pending',
            'total_price' => 0,
        ]);
        $test = $this->createTest('VETRAT1');
        $vat = VetAdmissionTestModel::query()->create([
            'vet_admission_id' => $vet->id,
            'test_id' => $test->id,
            'price' => 0,
            'nbu_units' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('vet.admissions.loadResults', $vet), [
            'results' => [
                [
                    'id' => $vat->id,
                    'result' => '180',
                    'unit' => 'mg/dL',
                    'reference_value' => '60 - 110',
                    'method' => 'Enzimático',
                    'is_ratified' => '1',
                ],
            ],
        ]);

        $vat->refresh();
        $this->assertTrue($vat->is_ratified);
        $this->assertNotNull($vat->ratified_at);
        $this->assertSame($user->id, $vat->ratified_by);
    }

    public function test_sample_save_results_persiste_is_ratified_para_usuario_con_permiso_validar(): void
    {
        $user = $this->userWithPermissions(['samples.section', 'samples-results.create', 'samples.validate']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Aguas',
            'taxId' => '30-77777770-2',
            'status' => 'activo',
            'type' => ['comun'],
        ]);
        $sample = Sample::query()->create([
            'customer_id' => $customer->id,
            'protocol_number' => 'A-2026-RAT001',
            'sample_type' => 'agua',
            'entry_date' => now()->toDateString(),
            'sampling_date' => now()->toDateString(),
            'location' => 'Cliente origen',
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
        $test = $this->createTest('SAMPLERAT1');
        $det = SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'price' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('sample.saveResults', $sample), [
            'determinations' => [
                [
                    'id' => $det->id,
                    'result' => '< 1',
                    'reference_value' => 'Ausencia/100ml',
                    'method' => 'Filtración',
                    'observations' => '',
                    'status' => 'completed',
                    'is_ratified' => '1',
                ],
            ],
        ]);

        $det->refresh();
        $this->assertTrue($det->is_ratified);
        $this->assertNotNull($det->ratified_at);
        $this->assertSame($user->id, $det->ratified_by);
    }
}
