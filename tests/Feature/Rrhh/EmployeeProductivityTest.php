<?php

namespace Tests\Feature\Rrhh;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Job;
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
        $this->assertSame(1, $row['metrics']['delivery']['results_delivered']);
    }

    public function test_bioquimico_email_sent_cuenta_como_entregado(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Email', 'is_central' => false, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('bioquimico');
        $this->makeEmployee($user, 'Clara', 'Bio');

        $patient = Patient::query()->create([
            'name' => 'P', 'lastName' => 'E', 'patientId' => '30555555',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-EMAIL',
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

        $admission->logAudit('email_sent', 'Envió resultados por email a test@example.com', $user->id);

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Clara Bio');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['metrics']['delivery']['results_delivered']);
        $this->assertArrayNotHasKey('results_delivered', $row['metrics']['reception'] ?? []);
    }

    public function test_entregado_no_duplica_email_y_result_delivered_mismo_protocolo(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Dup', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');
        $this->makeEmployee($user, 'Rep', 'Dup');

        $patient = Patient::query()->create([
            'name' => 'X', 'lastName' => 'Y', 'patientId' => '30666666',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-DUP',
            'lab_branch_id' => $branch->id,
            'status' => 'validated',
        ]);
        $test = $this->makeTestModel();
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 10,
            'is_validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'result' => '1',
        ]);

        $admission->logAudit('email_sent', 'Envió resultados por email a a@b.com', $user->id);
        LogsProtocolDelivery::logResultDeliveredOncePerDay($admission, $user->id);

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Rep Dup');

        $this->assertSame(1, $row['metrics']['delivery']['results_delivered']);
    }

    public function test_tecnico_solo_extraccion_aparece_en_productividad(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Extracción', 'is_central' => true, 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $technician = User::factory()->create();
        $technician->assignRole('tecnico-lab');
        $this->makeEmployee($technician, 'Roxana', 'Test');

        $patient = Patient::query()->create([
            'name' => 'Rodrigo', 'lastName' => 'Suarez', 'patientId' => '30123456',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $this->makeAdmission($admin, $patient, [
            'protocol_number' => 'C-KPI-DRAW',
            'lab_branch_id' => $branch->id,
            'sample_drawn_by' => $technician->id,
            'sample_drawn_at' => now(),
        ]);

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Roxana Test');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['metrics']['technician']['samples_drawn']);
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
        $this->assertSame(2, $row['metrics']['loading']['results_entered']);
        $this->assertSame(1, $row['metrics']['loading']['protocols_with_results']);
    }

    public function test_bioquimico_results_loaded_audit_cuenta_carga(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Bio Carga', 'is_central' => false, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('bioquimico');
        $this->makeEmployee($user, 'Clara', 'Carga');

        $patient = Patient::query()->create([
            'name' => 'P', 'lastName' => 'C', 'patientId' => '30777777',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-LOAD',
            'lab_branch_id' => $branch->id,
        ]);
        $test = $this->makeTestModel();
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 20,
            'result' => '12.5',
            'unit' => 'g/L',
        ]);

        $admission->logAudit('results_loaded', 'Cargó resultados en la admisión Nº '.$admission->protocol_number, $user->id);

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Clara Carga');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['metrics']['loading']['results_entered']);
        $this->assertSame(1, $row['metrics']['loading']['protocols_with_results']);
    }

    public function test_director_tecnico_puesto_recibe_metricas_bioquimico_sin_rol_spatie(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Dir', 'is_central' => false, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $employee = $this->makeEmployee($user, 'Director', 'Tecnico');
        $job = Job::query()->create(['name' => 'Director Técnico Neuquén']);
        $employee->jobs()->attach($job->id, ['user_id' => $user->id]);

        $patient = Patient::query()->create([
            'name' => 'D', 'lastName' => 'T', 'patientId' => '30444444',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-005',
            'lab_branch_id' => $branch->id,
            'status' => 'completed',
        ]);
        $test = $this->makeTestModel();
        $at = AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 20,
            'result' => '4.0',
            'unit' => 'g/L',
        ]);

        $this->actingAs($user)->post(route('lab.admissions.validateTest', [$admission, $at]))
            ->assertRedirect();

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $row = collect($report['rows'])->firstWhere('employee_name', 'Director Tecnico');

        $this->assertNotNull($row);
        $this->assertContains('director-tecnico', $row['roles']);
        $this->assertNotContains('bioquimico', $row['roles']);
        $this->assertGreaterThanOrEqual(1, $row['metrics']['biochemist']['tests_validated']);
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

    public function test_filas_ordenadas_por_rol_y_luego_nombre(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Sort', 'is_central' => true, 'is_active' => true]);

        $recep = User::factory()->create();
        $recep->assignRole('recepcion-lab');
        $this->makeEmployee($recep, 'Zoe', 'Recep');

        $tec = User::factory()->create();
        $tec->assignRole('tecnico-lab');
        $this->makeEmployee($tec, 'Ana', 'Tec');

        $bio = User::factory()->create();
        $bio->assignRole('bioquimico');
        $this->makeEmployee($bio, 'Bea', 'Bio');

        $patient = Patient::query()->create([
            'name' => 'S', 'lastName' => 'O', 'patientId' => '30888888',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        foreach ([[$recep, 'C-SORT-1'], [$tec, 'C-SORT-2'], [$bio, 'C-SORT-3']] as [$user, $protocol]) {
            $adm = $this->makeAdmission($user, $patient, [
                'protocol_number' => $protocol,
                'lab_branch_id' => $branch->id,
            ]);
            $adm->logAudit('created', 'Creó la admisión', $user->id);
        }

        $report = app(EmployeeProductivityService::class)->report(now(), $branch->id);
        $names = collect($report['rows'])->pluck('employee_name')->values()->all();

        $this->assertSame(['Zoe Recep', 'Ana Tec', 'Bea Bio'], $names);
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

    public function test_fila_productividad_navega_a_detalle_empleado(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Link', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');
        $employee = $this->makeEmployee($user, 'Link', 'Recep');

        $patient = Patient::query()->create([
            'name' => 'P', 'lastName' => 'L', 'patientId' => '30101010',
            'type' => 'humano', 'sex' => 'M', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-LINK',
            'lab_branch_id' => $branch->id,
        ]);
        $admission->logAudit('created', 'Creó la admisión', $user->id);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('rrhh.productividad'))
            ->assertOk()
            ->assertSee(route('rrhh.productividad.empleado', $employee->id), false);
    }

    public function test_productividad_individual_rango_default_30_dias(): void
    {
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');
        $employee = $this->makeEmployee($user, 'Rango', 'Default');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $expectedFrom = now()->subDays(29)->toDateString();
        $expectedTo = now()->toDateString();

        $this->actingAs($admin)
            ->get(route('rrhh.productividad.empleado', $employee))
            ->assertOk()
            ->assertSee('Rango Default')
            ->assertSee('Desglose mensual')
            ->assertSee($expectedFrom, false)
            ->assertSee($expectedTo, false);
    }

    public function test_recepcionista_productividad_individual_sin_metricas_validacion(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Recep Ind', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');
        $employee = $this->makeEmployee($user, 'Solo', 'Recep');

        $patient = Patient::query()->create([
            'name' => 'P', 'lastName' => 'R', 'patientId' => '30202020',
            'type' => 'humano', 'sex' => 'F', 'status' => 'activo',
        ]);
        $admission = $this->makeAdmission($user, $patient, [
            'protocol_number' => 'C-KPI-RECEP-IND',
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

        $response = $this->actingAs($admin)
            ->get(route('rrhh.productividad.empleado', $employee));

        $response->assertOk()
            ->assertSee('Protocolos creados')
            ->assertSee('Resultados entregados')
            ->assertDontSee('Val. práct.', false)
            ->assertDontSee('Det. carg.', false)
            ->assertDontSee('Extracciones', false);

        $report = app(EmployeeProductivityService::class)->employeePeriodReport(
            $employee->load(['user.roles', 'jobs']),
            now()->subDays(29),
            now(),
            $branch->id
        );

        $this->assertNotNull($report);
        $this->assertContains('reception', $report['applicable_groups']);
        $this->assertContains('delivery', $report['applicable_groups']);
        $this->assertNotContains('biochemist', $report['applicable_groups']);
        $this->assertNotContains('loading', $report['applicable_groups']);
    }

    public function test_tecnico_productividad_individual_sin_metricas_recepcion(): void
    {
        $branch = LabBranch::query()->create(['name' => 'Sede Tec Ind', 'is_central' => true, 'is_active' => true]);
        $user = User::factory()->create();
        $user->assignRole('tecnico-lab');
        $employee = $this->makeEmployee($user, 'Solo', 'Tec');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('rrhh.productividad.empleado', $employee))
            ->assertOk()
            ->assertSee('Det. carg.')
            ->assertSee('Extracciones')
            ->assertDontSee('Protocolos creados', false)
            ->assertDontSee('Val. práct.', false);

        $report = app(EmployeeProductivityService::class)->employeePeriodReport(
            $employee->load(['user.roles', 'jobs']),
            now()->subDays(29),
            now(),
            $branch->id
        );

        $this->assertContains('loading', $report['applicable_groups']);
        $this->assertContains('technician', $report['applicable_groups']);
        $this->assertNotContains('reception', $report['applicable_groups']);
        $this->assertNotContains('biochemist', $report['applicable_groups']);
    }
}
