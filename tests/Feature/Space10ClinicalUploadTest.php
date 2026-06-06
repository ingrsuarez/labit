<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Space10ClinicalUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->enableSpace10Config();
    }

    private function enableSpace10Config(): void
    {
        Config::set('space10.enabled', true);
        Config::set('space10.api_url', 'https://space10.test/api/upload/lab');
        Config::set('space10.api_token', 'test-token');
        Config::set('space10.timeout', 5);
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
            'code' => 'S10T',
            'name' => 'Test Space10',
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

    /**
     * @return array{0: User, 1: LabBranch, 2: Admission, 3: Test}
     */
    private function makeValidatedAdmission(?string $dni = '30111222'): array
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Space10',
            'is_central' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Space10',
            'patientId' => $dni,
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $protocol = Admission::generateProtocolNumber();
        $admission = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => $protocol,
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
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        return [$user, $branch, $admission, $test];
    }

    public function test_send_email_sube_a_space10_y_setea_space10_uploaded_at(): void
    {
        Mail::fake();
        Http::fake([
            'https://space10.test/*' => Http::response(['path' => 'patients/30111222/lab/file.pdf'], 201),
        ]);

        [$user, , $admission] = $this->makeValidatedAdmission();

        $response = $this->actingAs($user)->post(route('lab.admissions.sendEmail', $admission), [
            'email' => 'paciente@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull($admission->fresh()->sent_at);
        $this->assertNotNull($admission->fresh()->space10_uploaded_at);
        Http::assertSentCount(1);
    }

    public function test_send_email_no_reintenta_si_ya_subido(): void
    {
        Mail::fake();
        Http::fake();

        [$user, , $admission] = $this->makeValidatedAdmission();
        $admission->update(['space10_uploaded_at' => now()->subDay()]);

        $this->actingAs($user)->post(route('lab.admissions.sendEmail', $admission), [
            'email' => 'paciente@example.com',
        ]);

        Http::assertNothingSent();
        $this->assertNotNull($admission->fresh()->sent_at);
    }

    public function test_send_email_continua_si_space10_falla(): void
    {
        Mail::fake();
        Http::fake([
            'https://space10.test/*' => Http::response(['error' => 'Paciente no encontrado'], 404),
        ]);

        [$user, , $admission] = $this->makeValidatedAdmission();

        $response = $this->actingAs($user)->post(route('lab.admissions.sendEmail', $admission), [
            'email' => 'paciente@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');
        $this->assertNotNull($admission->fresh()->sent_at);
        $this->assertNull($admission->fresh()->space10_uploaded_at);
    }

    public function test_batch_space10_sube_multiples_y_skips_ya_subidos(): void
    {
        Http::fake([
            'https://space10.test/*' => Http::response(['path' => 'ok'], 201),
        ]);

        [$user, , $admission1, $test] = $this->makeValidatedAdmission('30111222');

        $patient = $admission1->patient;
        $protocol2 = Admission::generateProtocolNumber();
        $admission2 = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '2',
            'protocol_number' => $protocol2,
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
            'lab_branch_id' => $admission1->lab_branch_id,
            'space10_uploaded_at' => now()->subDay(),
        ]);

        AdmissionTest::query()->create([
            'admission_id' => $admission2->id,
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-space10'), [
            'admission_ids' => [$admission1->id, $admission2->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['uploaded' => [$admission1->protocol_number]]);
        $data = $response->json();
        $this->assertStringContainsString('ya subido a Space10', implode(' ', $data['skipped']));
        $this->assertNotNull($admission1->fresh()->space10_uploaded_at);
        Http::assertSentCount(1);
    }

    public function test_batch_space10_skip_sin_dni(): void
    {
        Http::fake();

        [$user, , $admission] = $this->makeValidatedAdmission('');

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-space10'), [
            'admission_ids' => [$admission->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('uploaded', []);
        $data = $response->json();
        $this->assertStringContainsString('sin DNI', implode(' ', $data['skipped']));
        Http::assertNothingSent();
    }

    public function test_batch_space10_deshabilitado_marca_skipped(): void
    {
        Config::set('space10.enabled', false);
        Http::fake();

        [$user, , $admission] = $this->makeValidatedAdmission();

        $response = $this->actingAs($user)->postJson(route('lab.admissions.batch-space10'), [
            'admission_ids' => [$admission->id],
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertStringContainsString('Space10 deshabilitado', implode(' ', $data['skipped']));
        Http::assertNothingSent();
    }
}
