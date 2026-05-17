<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\User;
use App\Services\ProtocolStatusCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProtocolStatusUnifiedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
        Permission::findOrCreate('lab-admissions.index');
        Role::findOrCreate('bioquimico')->givePermissionTo(['lab.section', 'lab-admissions.index']);
    }

    public function test_admission_calculated_status_partially_validated(): void
    {
        $admission = $this->createAdmission();
        $testA = $this->createTest('PSU1');
        $testB = $this->createTest('PSU2');

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $testA->id,
            'result' => '1.0',
            'is_validated' => true,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
            'price' => 100,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $testB->id,
            'result' => '2.0',
            'is_validated' => false,
            'authorization_status' => AdmissionTest::STATUS_AUTHORIZED,
            'price' => 100,
        ]);

        $admission->load('admissionTests');

        $this->assertSame(
            ProtocolStatusCalculator::STATUS_PARTIALLY_VALIDATED,
            $admission->calculated_status
        );
    }

    public function test_sample_calculated_status_ignores_sent_at(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Cliente PSU',
            'taxId' => '30-11111111-1',
            'status' => 'activo',
            'type' => ['comun'],
        ]);

        $sample = Sample::query()->create([
            'customer_id' => $customer->id,
            'protocol_number' => 'A-2026-PSU001',
            'sample_type' => 'agua',
            'entry_date' => now()->toDateString(),
            'sampling_date' => now()->toDateString(),
            'location' => 'Planta',
            'status' => 'validated',
            'validation_status' => 'validated',
            'sent_at' => now(),
            'created_by' => $user->id,
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $this->createTest('PSUS1')->id,
            'result' => '10',
            'price' => 0,
            'status' => 'completed',
            'is_validated' => true,
        ]);

        $sample->load('determinations');

        $this->assertSame('validated', $sample->calculated_status);
        $this->assertNotSame('enviado', $sample->calculated_status);
    }

    public function test_lab_index_filter_enviado_uses_sent_at_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bioquimico');

        $sent = $this->createAdmission([
            'status' => ProtocolStatusCalculator::STATUS_IN_PROGRESS,
            'sent_at' => now(),
        ]);
        $notSent = $this->createAdmission([
            'status' => ProtocolStatusCalculator::STATUS_VALIDATED,
            'sent_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('lab.admissions.index', ['status' => 'enviado']));

        $response->assertOk();
        $response->assertSee($sent->protocol_number);
        $response->assertDontSee($notSent->protocol_number);
    }

    private function createTest(string $code): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Test '.$code,
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

    private function createAdmission(array $attrs = []): Admission
    {
        static $n = 1;
        $num = $n++;

        return Admission::create(array_merge([
            'date' => now(),
            'number' => $num,
            'protocol_number' => 'C-2026-T'.str_pad((string) $num, 4, '0', STR_PAD_LEFT),
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
