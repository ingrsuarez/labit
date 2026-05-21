<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\InvoiceProtocol;
use App\Models\Patient;
use App\Models\SalesInvoice;
use App\Models\Species;
use App\Models\Test;
use App\Models\User;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NbuRetroactivePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        return $user;
    }

    public function test_cambiar_nbu_sin_retroactivo_no_modifica_admisiones(): void
    {
        $user = $this->authorizedUser();
        $insurance = Insurance::query()->create([
            'name' => 'os inmutable',
            'type' => 'obra_social',
            'nbu_value' => 100,
        ]);

        $test = Test::query()->create([
            'code' => 'RETRO1',
            'name' => 'Practica retro',
            'nbu' => 2,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $admission = $this->makeClinicalAdmission($user, $insurance, now()->subDay()->toDateString());
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 200,
            'nbu_units' => 2,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)->put(route('nomenclator.updateNbu', $insurance), [
            'nbu_value' => 150,
        ])->assertRedirect();

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 200,
        ]);
    }

    public function test_retroactivo_clinico_recalcula_admision_no_facturada(): void
    {
        $user = $this->authorizedUser();
        $base = Insurance::query()->create([
            'name' => 'base retro',
            'type' => 'nomenclador',
            'nbu_value' => 1,
        ]);

        $insurance = Insurance::query()->create([
            'name' => 'os retroactiva',
            'type' => 'obra_social',
            'nbu_value' => 100,
            'nomenclator_id' => $base->id,
        ]);

        $test = Test::query()->create([
            'code' => 'RETRO2',
            'name' => 'Practica base retro',
            'nbu' => 1,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        InsuranceTest::query()->create([
            'insurance_id' => $base->id,
            'test_id' => $test->id,
            'nbu_units' => 5,
            'price' => 5,
        ]);

        $admission = $this->makeClinicalAdmission($user, $insurance, now()->subDay()->toDateString());
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 500,
            'nbu_units' => 5,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)->put(route('nomenclator.updateNbu', $insurance), [
            'nbu_value' => 120,
            'retroactive_update' => 1,
            'retroactive_from' => now()->subDays(2)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 600,
            'nbu_units' => 5,
        ]);
    }

    public function test_retroactivo_excluye_admisiones_facturadas(): void
    {
        $user = $this->authorizedUser();
        $insurance = Insurance::query()->create([
            'name' => 'os facturada',
            'type' => 'obra_social',
            'nbu_value' => 100,
        ]);

        $test = Test::query()->create([
            'code' => 'RETRO3',
            'name' => 'Practica facturada',
            'nbu' => 2,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $admission = $this->makeClinicalAdmission($user, $insurance, now()->toDateString());
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 200,
            'nbu_units' => 2,
            'authorization_status' => 'not_required',
        ]);

        $invoice = SalesInvoice::query()->create([
            'invoice_number' => 'TEST-001',
            'voucher_type' => 'B',
            'issue_date' => now()->toDateString(),
            'subtotal' => 200,
            'total' => 200,
            'amount_collected' => 0,
            'balance' => 200,
            'status' => 'pendiente',
            'created_by' => $user->id,
        ]);

        InvoiceProtocol::query()->create([
            'sales_invoice_id' => $invoice->id,
            'protocol_type' => Admission::class,
            'protocol_id' => $admission->id,
            'amount' => 200,
        ]);

        $this->actingAs($user)->put(route('nomenclator.updateNbu', $insurance), [
            'nbu_value' => 150,
            'retroactive_update' => 1,
            'retroactive_from' => now()->subDay()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 200,
        ]);
    }

    public function test_retroactivo_respeta_fecha_corte(): void
    {
        $user = $this->authorizedUser();
        $insurance = Insurance::query()->create([
            'name' => 'os fecha corte',
            'type' => 'laborales',
            'nbu_value' => 100,
        ]);

        $test = Test::query()->create([
            'code' => 'RETRO4',
            'name' => 'Practica corte',
            'nbu' => 2,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $oldAdmission = $this->makeClinicalAdmission($user, $insurance, now()->subDays(10)->toDateString());
        AdmissionTest::query()->create([
            'admission_id' => $oldAdmission->id,
            'test_id' => $test->id,
            'price' => 200,
            'nbu_units' => 2,
            'authorization_status' => 'not_required',
        ]);

        $this->actingAs($user)->put(route('nomenclator.updateNbu', $insurance), [
            'nbu_value' => 150,
            'retroactive_update' => 1,
            'retroactive_from' => now()->subDays(3)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $oldAdmission->id,
            'test_id' => $test->id,
            'price' => 200,
        ]);
    }

    public function test_retroactivo_veterinario_recalcula_protocolo_no_facturado(): void
    {
        $user = $this->authorizedUser();
        $customer = Customer::query()->create([
            'name' => 'Vet Retro',
            'taxId' => '30123456789',
            'status' => 'activo',
            'type' => ['veterinario'],
            'veterinary_nbu_value' => 100,
        ]);

        $species = Species::query()->create(['name' => 'Canino', 'code' => 'CAN', 'is_active' => true]);
        $test = Test::query()->create([
            'code' => 'VRETRO1',
            'name' => 'Practica vet retro',
            'nbu' => 2,
            'price' => 0,
            'categories' => ['veterinario'],
        ]);

        $vetAdmission = VetAdmission::query()->create([
            'protocol_number' => 'V-2026-RETRO1',
            'date' => now()->subDay()->toDateString(),
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'Firulais',
            'owner_name' => 'Dueño',
            'status' => 'pending',
            'total_price' => 200,
            'created_by' => $user->id,
        ]);

        VetAdmissionTest::query()->create([
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $test->id,
            'price' => 200,
            'nbu_units' => 2,
        ]);

        $this->actingAs($user)->put(route('customer.update', $customer), [
            'name' => $customer->name,
            'taxId' => $customer->taxId,
            'status' => 'activo',
            'type' => ['veterinario'],
            'veterinary_nbu_value' => 120,
            'retroactive_update' => 1,
            'retroactive_from' => now()->subDays(2)->toDateString(),
        ])->assertRedirect(route('customer.index'));

        $this->assertDatabaseHas('vet_admission_tests', [
            'vet_admission_id' => $vetAdmission->id,
            'test_id' => $test->id,
            'price' => 240,
        ]);

        $this->assertDatabaseHas('vet_admissions', [
            'id' => $vetAdmission->id,
            'total_price' => 240,
        ]);
    }

    public function test_preview_retroactivo_clinico_devuelve_contadores(): void
    {
        $user = $this->authorizedUser();
        $insurance = Insurance::query()->create([
            'name' => 'os preview',
            'type' => 'obra_social',
            'nbu_value' => 100,
        ]);

        $test = Test::query()->create([
            'code' => 'PREV1',
            'name' => 'Practica preview',
            'nbu' => 2,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $admission = $this->makeClinicalAdmission($user, $insurance, now()->toDateString());
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 200,
            'nbu_units' => 2,
            'authorization_status' => 'not_required',
        ]);

        $response = $this->actingAs($user)->postJson(route('nomenclator.previewRetroactiveNbu', $insurance), [
            'new_nbu_value' => 150,
            'from_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('admissions_count', 1);
        $response->assertJsonPath('rows_count', 1);
        $response->assertJsonPath('excluded_invoiced_count', 0);
    }

    private function makeClinicalAdmission(User $user, Insurance $insurance, string $date): Admission
    {
        $patient = Patient::query()->create([
            'name' => 'Paciente',
            'lastName' => 'Retro',
            'patientId' => '30999888',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        return Admission::query()->create([
            'date' => $date,
            'patient_id' => $patient->id,
            'protocol_number' => 'C-2026-R'.random_int(100, 999),
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => $date,
            'promise_date' => $date,
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance' => $insurance->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);
    }
}
