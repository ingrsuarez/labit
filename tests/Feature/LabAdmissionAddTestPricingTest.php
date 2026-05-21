<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabAdmissionAddTestPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
    }

    public function test_add_test_usa_nbu_units_del_nomenclador_base(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $baseNomenclator = Insurance::query()->create([
            'name' => 'nomenclador base test',
            'type' => 'nomenclador',
            'nbu_value' => 1,
        ]);

        $insurance = Insurance::query()->create([
            'name' => 'os test add pricing',
            'type' => 'obra_social',
            'nbu_value' => 100,
            'nomenclator_id' => $baseNomenclator->id,
        ]);

        $test = Test::query()->create([
            'code' => 'ADDNBU1',
            'name' => 'Practica nomenclador base',
            'nbu' => 1,
            'price' => 9999,
            'categories' => ['clinico'],
        ]);

        InsuranceTest::query()->create([
            'insurance_id' => $baseNomenclator->id,
            'test_id' => $test->id,
            'nbu_units' => 5,
            'price' => 5,
        ]);

        $admission = $this->makeAdmission($user, $insurance);

        $response = $this->actingAs($user)->post(route('lab.admissions.addTest', $admission), [
            'test_id' => $test->id,
            'price' => 9999,
            'authorization_status' => 'not_required',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 500,
            'nbu_units' => 5,
        ]);
    }

    public function test_add_test_usa_fallback_nbu_para_laborales_sin_nomenclador(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $insurance = Insurance::query()->create([
            'name' => 'empresa laboral add',
            'type' => 'laborales',
            'nbu_value' => 100,
            'nomenclator_id' => null,
        ]);

        $test = Test::query()->create([
            'code' => 'ADDNBU2',
            'name' => 'Practica laboral fallback',
            'nbu' => 2.5,
            'price' => 0,
            'categories' => ['clinico'],
        ]);

        $admission = $this->makeAdmission($user, $insurance);

        $this->actingAs($user)->post(route('lab.admissions.addTest', $admission), [
            'test_id' => $test->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ])->assertRedirect();

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'test_id' => $test->id,
            'price' => 250,
            'nbu_units' => 2.5,
        ]);
    }

    public function test_add_test_no_usa_precio_generico_del_test(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('lab.section');

        $baseNomenclator = Insurance::query()->create([
            'name' => 'base no generico',
            'type' => 'nomenclador',
            'nbu_value' => 1,
        ]);

        $insurance = Insurance::query()->create([
            'name' => 'os no generico',
            'type' => 'obra_social',
            'nbu_value' => 80,
            'nomenclator_id' => $baseNomenclator->id,
        ]);

        $test = Test::query()->create([
            'code' => 'ADDNBU3',
            'name' => 'Practica precio generico alto',
            'nbu' => 3,
            'price' => 9999,
            'categories' => ['clinico'],
        ]);

        InsuranceTest::query()->create([
            'insurance_id' => $baseNomenclator->id,
            'test_id' => $test->id,
            'nbu_units' => 2,
            'price' => 2,
        ]);

        $admission = $this->makeAdmission($user, $insurance);

        $this->actingAs($user)->post(route('lab.admissions.addTest', $admission), [
            'test_id' => $test->id,
            'price' => 9999,
            'authorization_status' => 'not_required',
        ])->assertRedirect();

        $row = AdmissionTest::query()
            ->where('admission_id', $admission->id)
            ->where('test_id', $test->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(160.0, (float) $row->price);
        $this->assertNotSame(9999.0, (float) $row->price);
    }

    private function makeAdmission(User $user, Insurance $insurance): Admission
    {
        $patient = Patient::query()->create([
            'name' => 'Paciente',
            'lastName' => 'AddTest',
            'patientId' => '40111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => 'C-2026-ADD01',
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
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
