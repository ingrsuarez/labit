<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
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

    private function makeTest(string $code, ?int $parent = null, int $sortOrder = 0, int $price = 0): Test
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
        preg_match_all('#<tr class="hover:bg-gray-50">\s*<td class="px-4 py-3 whitespace-nowrap">\s*<a[^>]*class="pending-protocol-link[^"]*"[^>]*>\s*([^<]+?)\s*</a>#', $html, $m);
        $this->assertCount(2, $m[1], 'Debe haber dos filas con protocolo en la tabla.');
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
}
