<?php

namespace Tests\Feature\Rrhh;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Services\EmployeeProductivityService;
use App\Support\LogsProtocolDelivery;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\RrhhProductivityPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeProductivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RrhhProductivityPermissionsSeeder::class);
    }

    private function makeEmployee(User $user, string $name, string $lastName): Employee
    {
        return Employee::query()->create([
            'name' => $name,
            'lastName' => $lastName,
            'employeeId' => 'EMP-'.$user->id,
            'user_id' => $user->id,
            'sex' => 'M',
            'status' => 'active',
        ]);
    }

    private function makeAdmission(User $user, Patient $patient, array $overrides = []): Admission
    {
        return Admission::query()->create(array_merge([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-KPI-'.uniqid(),
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
            'authorization_code' => '',
            'attended_by' => $user->id,
            'created_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'status' => 'pending',
            'created_at' => now(),
        ], $overrides));
    }

    private function makeTestModel(): Test
    {
        return Test::query()->create([
            'code' => 'KPI1',
            'name' => 'Test KPI',
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

    public function test_recepcion_lab_recibe_403_en_productividad(): void
    {
        $user = User::factory()->create();
        Role::findByName('recepcion-lab', 'web')->givePermissionTo('lab.section');

        $response = $this->actingAs($user)->get(route('rrhh.productividad'));
        $this->assertFalse($response->isOk());
        $response->assertDontSee('Productividad diaria', false);
    }

    public function test_admin_ve_productividad_diaria(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('rrhh.productividad'))
            ->assertOk()
            ->assertSee('Productividad diaria');
    }

    public function test_result_delivered_es_idempotente_por_dia(): void
    {
        $user = User::factory()->create();
        $patient = Patient::query()->create([
            'name' => 'Ana', 'lastName' => 'KPI', 'patientId' => '30111222',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-001',
            'status' => 'validated',
        ]);
        $test = $this->makeTestModel();
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 100,
            'is_validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'result' => '10',
        ]);

        LogsProtocolDelivery::logResultDeliveredOncePerDay($admission, $user->id);
        LogsProtocolDelivery::logResultDeliveredOncePerDay($admission, $user->id);
        LogsProtocolDelivery::logResultDeliveredOncePerDay($admission, $user->id);

        $count = AuditLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'result_delivered')
            ->where('auditable_type', Admission::class)
            ->where('auditable_id', $admission->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_recepcionista_metrica_entregados(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede KPI', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');
        $this->makeEmployee($user, 'Recep', 'Test');

        $patient = Patient::query()->create([
            'name' => 'Pac', 'lastName' => 'Ent', 'patientId' => '30999888',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-002',
            'lab_branch_id' => $branch->id,
            'status' => 'validated',
        ]);
        $test = $this->makeTestModel();
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 50,
            'is_validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'result' => '5',
        ]);

        $admission->logAudit('created', 'Creó la admisión', $user->id);
        LogsProtocolDelivery::logResultDeliveredOncePerDay($admission, $user->id);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id, null, null);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Recep Test');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['metrics']['reception']['results_delivered']);
    }

    public function test_tecnico_result_entered_en_metricas(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Tec', 'is_central' => false, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('tecnico-lab');
        $this->makeEmployee($user, 'Tec', 'Lab');

        $patient = Patient::query()->create([
            'name' => 'P', 'lastName' => 'T', 'patientId' => '30111111',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-003',
            'lab_branch_id' => $branch->id,
        ]);
        $t1 = $this->makeTestModel();
        $t2 = Test::query()->create([
            'code' => 'KPI2',
            'name' => 'Test KPI 2',
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
        $at1 = AdmissionTest::query()->create(['admission_id' => $admission->id, 'test_id' => $t1->id, 'price' => 10]);
        $at2 = AdmissionTest::query()->create(['admission_id' => $admission->id, 'test_id' => $t2->id, 'price' => 10]);

        $this->actingAs($user)->post(route('lab.admissions.saveResults', $admission), [
            'results' => [
                ['id' => $at1->id, 'result' => '1.0', 'unit' => 'g/L'],
                ['id' => $at2->id, 'result' => '2.0', 'unit' => 'g/L'],
            ],
        ])->assertRedirect();

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Tec Lab');

        $this->assertNotNull($row);
        $this->assertSame(2, $row['metrics']['technician']['results_entered']);
        $this->assertSame(1, $row['metrics']['technician']['protocols_with_results']);
    }

    public function test_bioquimico_validacion_en_metricas(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Bio', 'is_central' => false, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('bioquimico');
        $this->makeEmployee($user, 'Bio', 'Q');

        $patient = Patient::query()->create([
            'name' => 'B', 'lastName' => 'P', 'patientId' => '30222222',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-004',
            'lab_branch_id' => $branch->id,
            'status' => 'completed',
        ]);
        $test = $this->makeTestModel();
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 20,
            'result' => '3.5',
            'unit' => 'g/L',
        ]);

        $this->actingAs($user)->post(route('lab.admissions.validateTest', [$admission, $at]))
            ->assertRedirect();

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Bio Q');

        $this->assertNotNull($row);
        $this->assertGreaterThanOrEqual(1, $row['metrics']['biochemist']['tests_validated']);
    }

    public function test_denominador_protocolos_creados_por_sede(): void
    {
        $branchA = LabBranch::query()->create(['name' => 'Sede A', 'is_central' => true, 'is_active' => true]);
        $branchB = LabBranch::query()->create(['name' => 'Sede B', 'is_central' => false, 'is_active' => true]);
        $patient = Patient::query()->create([
            'name' => 'X', 'lastName' => 'Y', 'patientId' => '30333333',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);

        $user = User::factory()->create();
        foreach (['C-A1', 'C-A2'] as $num) {
            $this->makeAdmission($user, $patient, [
                'protocol_number' => $num,
                'lab_branch_id' => $branchA->id,
            ]);
        }

        $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-B1',
            'lab_branch_id' => $branchB->id,
        ]);

        $report = app(EmployeeProductivityService::class)->report(now(), $branchA->id);

        $this->assertSame(2, $report['branch_summary']['protocols_created']);
    }

    public function test_export_csv_responde_ok(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('rrhh.productividad.export', ['date' => now()->toDateString()]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    }
}
