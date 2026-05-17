<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Patient;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabPendingResultsPlanillaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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

    private function makePatient(): Patient
    {
        return Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Test',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);
    }

    private function makeTest(string $code, ?int $parent = null, int $sortOrder = 0, int $price = 0, bool $emptyResultExempt = false): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'T '.$code,
            'unit' => 'g/L',
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => $parent,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => $price,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => $sortOrder,
            'empty_result_exempt' => $emptyResultExempt,
        ]);
    }

    private function makeAdmission(User $user, Patient $patient, string $protocol = 'C-2026-PLAN01', ?string $date = null): Admission
    {
        return Admission::query()->create([
            'date' => $date ?? now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => $protocol,
            'number' => '1',
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

    public function test_pending_results_ruta_responde_200_y_muestra_protocolo(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient, 'C-2026-PLAN-FEAT');
        $admission->update(['status' => 'in_progress']);

        $parent = $this->makeTest('FE-P', null, 1, 1000);
        $child = $this->makeTest('FE-C', $parent->id, 2, 0);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $child->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));

        $response->assertOk();
        $response->assertSee('C-2026-PLAN-FEAT', false);
        $response->assertSee('T FE-P', false);
        $response->assertSee('En Proceso', false);
    }

    public function test_pending_results_sin_permiso_index_devuelve_403(): void
    {
        $user = $this->userWithPermissions(['lab.section']);
        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertForbidden();
    }

    public function test_admision_cancelada_no_aparece_en_planilla(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient, 'C-2026-CANC-99');
        $admission->update(['status' => Admission::STATUS_CANCELLED]);

        $t = $this->makeTest('SOLO-1', null, 1, 500);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $t->id,
            'price' => 500,
            'authorization_status' => 'not_required',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertOk();
        $response->assertDontSee('C-2026-CANC-99', false);
    }

    public function test_pending_results_orden_mas_reciente_primero(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $adOld = $this->makeAdmission($user, $patient, 'C-2026-OLD-ORD', '2026-01-10');
        $adNew = $this->makeAdmission($user, $patient, 'C-2026-NEW-ORD', '2026-05-20');

        $parent = $this->makeTest('ORD-P', null, 1, 1000);
        $child = $this->makeTest('ORD-C', $parent->id, 2, 0);

        foreach ([$adOld, $adNew] as $admission) {
            AdmissionTest::query()->create([
                'admission_id' => $admission->id,
                'test_id' => $parent->id,
                'price' => 100,
                'authorization_status' => 'not_required',
            ]);
            AdmissionTest::query()->create([
                'admission_id' => $admission->id,
                'test_id' => $child->id,
                'price' => 0,
                'authorization_status' => 'not_required',
            ]);
        }

        $adOld->refresh();
        $adNew->refresh();
        $this->assertSame('2026-01-10', $adOld->date->toDateString());
        $this->assertSame('2026-05-20', $adNew->date->toDateString());

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertOk();
        $html = $response->getContent();
        preg_match_all('/class="pending-protocol-link[^"]*"[^>]*>\s*([^<]+?)\s*</', $html, $m);
        $this->assertCount(2, $m[1], 'Debe haber dos enlaces de protocolo en la tabla.');
        $this->assertSame('C-2026-NEW-ORD', trim($m[1][0]));
        $this->assertSame('C-2026-OLD-ORD', trim($m[1][1]));
    }

    public function test_pending_results_filtra_por_rango_de_fechas(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $adOut = $this->makeAdmission($user, $patient, 'C-2026-DATE-OUT', '2026-02-01');
        $adIn = $this->makeAdmission($user, $patient, 'C-2026-DATE-IN', '2026-05-15');

        $parent = $this->makeTest('DT-P', null, 1, 1000);
        $child = $this->makeTest('DT-C', $parent->id, 2, 0);

        foreach ([$adOut, $adIn] as $admission) {
            AdmissionTest::query()->create([
                'admission_id' => $admission->id,
                'test_id' => $parent->id,
                'price' => 100,
                'authorization_status' => 'not_required',
            ]);
            AdmissionTest::query()->create([
                'admission_id' => $admission->id,
                'test_id' => $child->id,
                'price' => 0,
                'authorization_status' => 'not_required',
            ]);
        }

        $url = route('lab.admissions.pending-results', [
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]);
        $response = $this->actingAs($user)->get($url);
        $response->assertOk();
        $response->assertSee('C-2026-DATE-IN', false);
        $response->assertDontSee('C-2026-DATE-OUT', false);
    }

    public function test_pending_results_enlace_protocolo_abre_nueva_pestana(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient, 'C-2026-TAB-LNK');

        $parent = $this->makeTest('TAB-P', null, 1, 1000);
        $child = $this->makeTest('TAB-C', $parent->id, 2, 0);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $child->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertOk();
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertSee('pending-protocol-link', false);
    }

    public function test_pending_results_incluye_protocolo_veterinario_cuando_hay_permisos(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Vet Cli Pend',
            'taxId' => '30-11111111-1',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);
        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'DOG-PEND-MRG',
            'is_active' => true,
        ]);

        $vetAdmission = VetAdmission::query()->create([
            'date' => '2026-05-18',
            'protocol_number' => 'V-2026-PEND-MRG',
            'status' => 'pending',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Luna',
            'owner_name' => 'María',
            'total_price' => 100,
            'created_by' => $user->id,
        ]);

        $parent = $this->makeTest('VPEND-P', null, 1, 100);
        $child = $this->makeTest('VPEND-C', $parent->id, 2, 0);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'status' => 'pending',
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $child->id,
            'price' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertOk();
        $response->assertSee('V-2026-PEND-MRG', false);
        $response->assertSee('Veterinario', false);
        $response->assertSee('Luna', false);
    }

    public function test_pending_results_no_lista_vet_sin_permisos_operativos_veterinarios(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Vet Sin Perm',
            'taxId' => '30-22222222-2',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);
        $species = Species::query()->create([
            'name' => 'Felino',
            'code' => 'CAT-NOVET',
            'is_active' => true,
        ]);

        $vetAdmission = VetAdmission::query()->create([
            'date' => '2026-05-19',
            'protocol_number' => 'V-2026-NOVET-IDX',
            'status' => 'pending',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Mimi',
            'owner_name' => 'Pedro',
            'total_price' => 50,
            'created_by' => $user->id,
        ]);

        $parent = $this->makeTest('NVET-P', null, 1, 50);
        $child = $this->makeTest('NVET-C', $parent->id, 2, 0);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $parent->id,
            'price' => 50,
            'status' => 'pending',
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $child->id,
            'price' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));
        $response->assertOk();
        $response->assertDontSee('V-2026-NOVET-IDX', false);
    }

    public function test_pending_results_omits_empty_result_exempt_and_hides_fully_loaded_vet_protocol(): void
    {
        $user = $this->userWithPermissions([
            'lab.section',
            'lab-admissions.index',
            'lab-admissions.show',
            'lab-results.create',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Vet Exempt Pend',
            'taxId' => '30-44444444-4',
            'status' => 'activo',
            'type' => ['veterinario'],
        ]);
        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'DOG-EX-PEND',
            'is_active' => true,
        ]);

        $vetAdmission = VetAdmission::query()->create([
            'date' => '2026-05-15',
            'protocol_number' => 'V-2026-EXEMPT-ONLY',
            'status' => 'in_progress',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Rulo',
            'owner_name' => 'Crespo',
            'total_price' => 100,
            'created_by' => $user->id,
        ]);

        $loaded = $this->makeTest('VEX-LOAD', null, 1, 100);
        $formula = $this->makeTest('VEX-FL', null, 2, 0, true);
        $formula->update(['name' => 'formula leucocitaria']);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $loaded->id,
            'result' => '10',
            'price' => 100,
            'status' => 'completed',
        ]);
        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $formula->id,
            'result' => null,
            'price' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('lab.admissions.pending-results'));

        $response->assertOk();
        $response->assertDontSee('formula leucocitaria', false);
        $response->assertDontSee('V-2026-EXEMPT-ONLY', false);
    }
}
