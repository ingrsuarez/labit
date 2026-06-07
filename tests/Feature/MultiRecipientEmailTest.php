<?php

namespace Tests\Feature;

use App\Mail\AdmissionResultMail;
use App\Mail\SampleBatchMail;
use App\Mail\SampleResultMail;
use App\Mail\VetAdmissionResultMail;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\EntityEmail;
use App\Models\Insurance;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MultiRecipientEmailTest extends TestCase
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
            Permission::findOrCreate($p, 'web');
        }
        $user->givePermissionTo($permissions);
    }

    private function customerWithEmails(): Customer
    {
        $customer = Customer::query()->create([
            'name' => 'Cliente Multi',
            'taxId' => '20-55555555-5',
            'email' => 'principal@test.com',
            'status' => 'activo',
            'type' => ['aguas'],
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Customer::class,
            'emailable_id' => $customer->id,
            'email' => 'principal@test.com',
            'label' => 'Resultados',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Customer::class,
            'emailable_id' => $customer->id,
            'email' => 'facturacion@test.com',
            'label' => 'Facturación',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        return $customer->fresh('emails');
    }

    public function test_sample_send_email_a_dos_destinatarios(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $customer = $this->customerWithEmails();
        $test = Test::query()->create([
            'code' => 'MULTI1',
            'name' => 'Test',
            'unit' => 'mg/dL',
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-2026-MULTI1',
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => 'validated',
            'created_by' => $user->id,
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('sample.sendEmail', $sample), [
            'email' => 'principal@test.com, facturacion@test.com',
        ]);

        $response->assertRedirect();
        Mail::assertSent(SampleResultMail::class, function (SampleResultMail $mail) {
            return $mail->hasTo('principal@test.com') && $mail->hasTo('facturacion@test.com');
        });
    }

    public function test_sample_batch_email_usa_recipient_emails_sin_override(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $customer = $this->customerWithEmails();
        $test = Test::query()->create([
            'code' => 'MULTI2',
            'name' => 'Test',
            'unit' => 'mg/dL',
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-2026-MULTI2',
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => 'validated',
            'created_by' => $user->id,
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('sample.batch-email'), [
            'sample_ids' => [$sample->id],
        ]);

        $response->assertOk();
        Mail::assertSent(SampleBatchMail::class, function (SampleBatchMail $mail) {
            return $mail->hasTo('principal@test.com') && $mail->hasTo('facturacion@test.com');
        });
    }

    public function test_vet_send_email_a_dos_destinatarios_del_cliente(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['vet.section', 'vet-admissions.show', 'vet-admissions.edit']);

        $customer = $this->customerWithEmails();
        $species = Species::query()->create(['name' => 'Canino', 'code' => 'CAN']);

        $vetAdmission = VetAdmission::query()->create([
            'date' => today()->toDateString(),
            'protocol_number' => 'V-2026-MULTI1',
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Dueño',
            'status' => 'completed',
            'total_price' => 100,
            'created_by' => $user->id,
        ]);

        $test = Test::query()->create([
            'code' => 'VMULTI1',
            'name' => 'Test vet',
            'unit' => 'mg/dL',
            'price' => 100,
            'categories' => ['veterinario'],
        ]);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $test->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('vet.admissions.sendEmail', $vetAdmission), [
            'email' => 'principal@test.com, facturacion@test.com',
        ]);

        $response->assertRedirect();
        Mail::assertSent(VetAdmissionResultMail::class, function (VetAdmissionResultMail $mail) {
            return $mail->hasTo('principal@test.com') && $mail->hasTo('facturacion@test.com');
        });
    }

    public function test_lab_send_email_a_dos_destinatarios_de_obra_social(): void
    {
        Mail::fake();

        $branch = LabBranch::query()->create([
            'name' => 'Sede Multi',
            'is_central' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.show']);

        $insurance = Insurance::query()->create([
            'name' => 'os multi',
            'type' => 'obra_social',
            'email' => 'os1@test.com',
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Insurance::class,
            'emailable_id' => $insurance->id,
            'email' => 'os1@test.com',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        EntityEmail::query()->create([
            'emailable_type' => Insurance::class,
            'emailable_id' => $insurance->id,
            'email' => 'os2@test.com',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $patient = Patient::query()->create([
            'name' => 'Paciente',
            'lastName' => 'Test',
            'patientId' => '30999888',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $admission = Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => Admission::generateProtocolNumber(),
            'patient_id' => $patient->id,
            'insurance' => $insurance->id,
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
            'lab_branch_id' => $branch->id,
            'status' => Admission::STATUS_COMPLETED,
        ]);

        $test = Test::query()->create([
            'code' => 'LMULTI1',
            'name' => 'Test lab',
            'unit' => 'mg/dL',
            'price' => 0,
            'categories' => ['lab'],
        ]);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 0,
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('lab.admissions.sendEmail', $admission), [
            'email' => 'os1@test.com, os2@test.com',
        ]);

        $response->assertRedirect();
        Mail::assertSent(AdmissionResultMail::class, function (AdmissionResultMail $mail) {
            return $mail->hasTo('os1@test.com') && $mail->hasTo('os2@test.com');
        });
    }

    public function test_email_invalido_devuelve_error_de_validacion(): void
    {
        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $customer = Customer::query()->create([
            'name' => 'Cliente',
            'taxId' => '20-66666666-6',
            'status' => 'activo',
            'type' => ['aguas'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-2026-INVAL',
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => 'validated',
            'created_by' => $user->id,
        ]);

        $test = Test::query()->create([
            'code' => 'INVAL1',
            'name' => 'Test',
            'unit' => 'mg/dL',
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);

        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => true,
            'validated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('sample.sendEmail', $sample), [
            'email' => 'no-valido, otro@malo',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
