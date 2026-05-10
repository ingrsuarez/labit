<?php

namespace Tests\Feature;

use App\Mail\AdmissionBatchMail;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionBatchEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $p) {
            Permission::findOrCreate($p);
        }
        $user->givePermissionTo($permissions);
    }

    private function makeTestModel(): Test
    {
        return Test::query()->create([
            'code' => 'BAT1',
            'name' => 'Test batch email',
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

    public function test_batch_email_envia_un_solo_mail_y_marca_sent_at(): void
    {
        Mail::fake();

        $branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'García',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $p1 = Admission::generateProtocolNumber();
        $a1 = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => $p1,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_VALIDATED,
            'lab_branch_id' => $branch->id,
        ]);

        $p2 = Admission::generateProtocolNumber();
        $a2 = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '2',
            'protocol_number' => $p2,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_VALIDATED,
            'lab_branch_id' => $branch->id,
        ]);

        $test = $this->makeTestModel();
        foreach ([$a1, $a2] as $adm) {
            AdmissionTest::query()->create([
                'admission_id' => $adm->id,
                'test_id' => $test->id,
                'price' => 0,
                'authorization_status' => 'not_required',
                'is_validated' => true,
                'validated_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-email'), [
            'admission_ids' => [$a1->id, $a2->id],
            'email' => 'destino@example.com',
            'message' => 'Mensaje opcional',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'sent' => [$p1, $p2],
        ]);

        Mail::assertSent(AdmissionBatchMail::class, function (AdmissionBatchMail $mail) {
            return $mail->admissions->count() === 2
                && $mail->customMessage === 'Mensaje opcional';
        });

        $this->assertNotNull($a1->fresh()->sent_at);
        $this->assertNotNull($a2->fresh()->sent_at);
    }

    public function test_batch_email_sin_validadas_va_a_skipped_sin_mail(): void
    {
        Mail::fake();

        $branch = LabBranch::query()->create([
            'name' => 'Sede Test',
            'is_central' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Bob',
            'lastName' => 'Test',
            'patientId' => '28999111',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $proto = Admission::generateProtocolNumber();
        $admission = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => $proto,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_PENDING,
            'lab_branch_id' => $branch->id,
        ]);

        $test = $this->makeTestModel();
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => false,
        ]);

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-email'), [
            'admission_ids' => [$admission->id],
            'email' => 'destino@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('sent', []);
        Mail::assertNothingSent();
    }

    public function test_batch_email_excluye_protocolo_de_otra_sede(): void
    {
        Mail::fake();

        $branchA = LabBranch::query()->create([
            'name' => 'Sede A',
            'is_central' => true,
            'is_active' => true,
        ]);
        $branchB = LabBranch::query()->create([
            'name' => 'Sede B',
            'is_central' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branchA->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Cecilia',
            'lastName' => 'Otra',
            'patientId' => '27777888',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $test = $this->makeTestModel();

        $pOk = Admission::generateProtocolNumber();
        $ok = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => $pOk,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_VALIDATED,
            'lab_branch_id' => $branchA->id,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $ok->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $pSkip = Admission::generateProtocolNumber();
        $other = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '2',
            'protocol_number' => $pSkip,
            'patient_id' => $patient->id,
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
            'status' => Admission::STATUS_VALIDATED,
            'lab_branch_id' => $branchB->id,
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $other->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-email'), [
            'admission_ids' => [$ok->id, $other->id],
            'email' => 'destino@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['sent' => [$pOk]]);
        $data = $response->json();
        $this->assertStringContainsString('sin acceso', implode(' ', $data['skipped']));

        Mail::assertSent(AdmissionBatchMail::class, fn (AdmissionBatchMail $mail) => $mail->admissions->count() === 1);
    }
}
