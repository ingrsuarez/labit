<?php

namespace Tests\Feature\Billing;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingSummaryClinicalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab-reports.index');
        Permission::findOrCreate('lab.section');
    }

    public function test_monthly_report_shows_one_row_per_protocol_with_merged_codes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-reports.index']);

        $insurance = Insurance::query()->create([
            'name' => 'Medicus Test',
            'type' => 'obra_social',
            'state' => 'activo',
        ]);

        $patient = Patient::query()->create([
            'name' => 'Silvina',
            'lastName' => 'Olie',
            'patientId' => '29145034',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $admission = $this->createAdmission([
            'insurance' => $insurance->id,
            'patient_id' => $patient->id,
            'date' => '2026-05-02',
        ]);

        $parent = $this->createTest('HEMO-P');
        $child = $this->createTest('HEMO-C');
        $parent->childTests()->attach($child->id, ['order' => 1]);
        $solo = $this->createTest('GLU01');

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 500,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $child->id,
            'price' => 200,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $solo->id,
            'price' => 100,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);

        $response = $this->actingAs($user)->get(route('lab.reports.monthly', [
            'insurance_id' => $insurance->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertSee('Olie', false);
        $response->assertSee('GLU01-HEMO-P', false);
        $response->assertDontSee('HEMO-C', false);
        $response->assertSee('$600,00', false);
        $response->assertSee('1 protocolo', false);
    }

    public function test_detailed_report_shows_one_row_per_practice_with_total(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-reports.index']);

        $insurance = Insurance::query()->create([
            'name' => 'OS Detallado',
            'type' => 'obra_social',
            'state' => 'activo',
        ]);

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Lopez',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $admission = $this->createAdmission([
            'insurance' => $insurance->id,
            'patient_id' => $patient->id,
            'date' => '2026-05-05',
            'affiliate_number' => '025494/01',
        ]);

        $parent = $this->createTest('475');
        $child = $this->createTest('475-C');
        $parent->childTests()->attach($child->id, ['order' => 1]);
        $solo = $this->createTest('412');

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 3250,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $child->id,
            'price' => 500,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $solo->id,
            'price' => 975,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);

        $response = $this->actingAs($user)->get(route('lab.reports.monthly', [
            'insurance_id' => $insurance->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
            'format' => 'detailed',
        ]));

        $response->assertOk();
        $response->assertSee('Lopez', false);
        $response->assertSee('025494/01', false);
        $response->assertSee('475', false);
        $response->assertSee('412', false);
        $response->assertDontSee('475-C', false);
        $response->assertSee('TOTAL A FACTURAR', false);
        $response->assertSee('$4.225,00', false);
        $response->assertSee('2 práctica', false);
    }

    public function test_exports_require_permission(): void
    {
        $insurance = Insurance::query()->create([
            'name' => 'OS Export',
            'type' => 'obra_social',
            'state' => 'activo',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('lab.reports.exportExcel', [
            'insurance_id' => $insurance->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));
        $this->assertFalse($response->isSuccessful());
    }

    public function test_exports_succeed_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-reports.index']);

        $insurance = Insurance::query()->create([
            'name' => 'OS Export OK',
            'type' => 'obra_social',
            'state' => 'activo',
        ]);

        $admission = $this->createAdmission([
            'insurance' => $insurance->id,
            'date' => '2026-05-10',
        ]);
        $test = $this->createTest('T001');
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 50,
            'copago' => 0,
            'paid_by_patient' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
        ]);

        $query = [
            'insurance_id' => $insurance->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ];

        $this->actingAs($user)->get(route('lab.reports.exportExcel', $query))->assertOk();

        $this->actingAs($user)->get(route('lab.reports.exportPdf', $query))->assertOk();
    }

    private function createTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Test '.$code,
            'unit' => 'mg/dL',
            'decimals' => 2,
            'price' => 0,
            'cost' => 0,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => 0,
            'empty_result_exempt' => false,
        ]);
    }

    private function createAdmission(array $attrs = []): Admission
    {
        static $n = 1;
        $num = $n++;

        return Admission::create(array_merge([
            'date' => now(),
            'number' => $num,
            'protocol_number' => 'C-2026-R'.str_pad((string) $num, 4, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now(),
            'promise_date' => now(),
            'authorization_code' => '',
            'attended_by' => 0,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => 0,
        ], $attrs));
    }
}
