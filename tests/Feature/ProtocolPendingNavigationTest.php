<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Customer;
use App\Models\LabBranch;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\Species;
use App\Models\User;
use App\Models\VetAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProtocolPendingNavigationTest extends TestCase
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

    private function makeClinicalAdmission(User $user, Patient $patient, LabBranch $branch, string $protocolNumber, string $status): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'number' => '1',
            'protocol_number' => $protocolNumber,
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
            'status' => $status,
            'lab_branch_id' => $branch->id,
            'sent_at' => null,
        ]);
    }

    public function test_clinical_next_pending_salta_validado_y_va_al_siguiente_elegible(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Nav',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Nav',
            'lastName' => 'Paciente',
            'patientId' => '30111223',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $a1 = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV-010', Admission::STATUS_PENDING);
        $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV-020', Admission::STATUS_VALIDATED);
        $a3 = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV-030', Admission::STATUS_PENDING);

        $response = $this->actingAs($user)->get(route('lab.admissions.next-pending', [
            'admission' => $a1,
            'lab_branch_id' => (string) $branch->id,
        ]));

        $response->assertRedirect(route('lab.admissions.show', [
            'admission' => $a3,
            'lab_branch_id' => (string) $branch->id,
        ]));
    }

    public function test_clinical_previous_pending_va_al_protocolo_menor(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Nav 2',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Nav2',
            'lastName' => 'Paciente',
            'patientId' => '30111224',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);

        $a1 = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV2-010', Admission::STATUS_PENDING);
        $a2 = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV2-020', Admission::STATUS_PENDING);

        $response = $this->actingAs($user)->get(route('lab.admissions.previous-pending', [
            'admission' => $a2,
            'lab_branch_id' => (string) $branch->id,
        ]));

        $response->assertRedirect(route('lab.admissions.show', [
            'admission' => $a1,
            'lab_branch_id' => (string) $branch->id,
        ]));
    }

    public function test_clinical_next_pending_sin_candidato_redirige_al_index_con_aviso(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Nav 3',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.show']);

        $patient = Patient::query()->create([
            'name' => 'Nav3',
            'lastName' => 'Paciente',
            'patientId' => '30111225',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $only = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV3-099', Admission::STATUS_PENDING);

        $response = $this->actingAs($user)->get(route('lab.admissions.next-pending', [
            'admission' => $only,
            'lab_branch_id' => (string) $branch->id,
        ]));

        $response->assertRedirect(route('lab.admissions.index', ['lab_branch_id' => (string) $branch->id]));
        $response->assertSessionHas('warning');
    }

    public function test_clinical_next_pending_sin_permiso_show_responde_403(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Nav 4',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['lab.section', 'lab-admissions.index', 'lab-admissions.edit']);

        $patient = Patient::query()->create([
            'name' => 'Nav4',
            'lastName' => 'Paciente',
            'patientId' => '30111226',
            'type' => 'humano',
            'sex' => 'M',
            'status' => 'activo',
        ]);

        $a1 = $this->makeClinicalAdmission($user, $patient, $branch, 'C-NAV4-010', Admission::STATUS_PENDING);

        $this->actingAs($user)->get(route('lab.admissions.next-pending', ['admission' => $a1]))
            ->assertForbidden();
    }

    public function test_vet_next_pending_redirige_al_siguiente_por_numero_de_protocolo(): void
    {
        Permission::findOrCreate('vet-admissions.edit');
        $user = User::factory()->create();

        $customer = Customer::query()->create([
            'name' => 'Vet Nav',
            'taxId' => '30-88888888-1',
            'status' => 'activo',
            'type' => ['veterinario'],
            'veterinary_nbu_value' => 100,
        ]);
        $species = Species::query()->create([
            'name' => 'Canino Nav',
            'code' => 'CNV',
            'is_active' => true,
        ]);

        $v1 = VetAdmission::query()->create([
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'A',
            'owner_name' => 'O',
            'protocol_number' => 'V-NAV-010',
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'pending',
            'total_price' => 0,
            'sent_at' => null,
        ]);
        $v2 = VetAdmission::query()->create([
            'customer_id' => $customer->id,
            'species_id' => $species->id,
            'animal_name' => 'B',
            'owner_name' => 'O',
            'protocol_number' => 'V-NAV-020',
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'pending',
            'total_price' => 0,
            'sent_at' => null,
        ]);

        $user->givePermissionTo('vet-admissions.edit');

        $this->actingAs($user)->get(route('vet.admissions.next-pending', ['vetAdmission' => $v1]))
            ->assertRedirect(route('vet.admissions.show', ['vetAdmission' => $v2]));
    }

    public function test_sample_next_pending_redirige_al_siguiente_muestra(): void
    {
        $branch = LabBranch::query()->create([
            'name' => 'Sede Sample Nav',
            'is_central' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['default_lab_branch_id' => $branch->id]);
        $this->grant($user, ['samples.section', 'samples.index']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Nav',
            'taxId' => '20-22222222-2',
            'status' => 'activo',
            'type' => ['comun'],
        ]);

        $s1 = Sample::query()->create([
            'customer_id' => $customer->id,
            'protocol_number' => 'A-NAV-010',
            'sample_type' => 'agua',
            'entry_date' => now()->toDateString(),
            'sampling_date' => now()->toDateString(),
            'location' => 'L1',
            'status' => 'pending',
            'validation_status' => 'pending',
            'created_by' => $user->id,
            'lab_branch_id' => $branch->id,
            'sent_at' => null,
        ]);
        $s2 = Sample::query()->create([
            'customer_id' => $customer->id,
            'protocol_number' => 'A-NAV-020',
            'sample_type' => 'agua',
            'entry_date' => now()->toDateString(),
            'sampling_date' => now()->toDateString(),
            'location' => 'L2',
            'status' => 'pending',
            'validation_status' => 'pending',
            'created_by' => $user->id,
            'lab_branch_id' => $branch->id,
            'sent_at' => null,
        ]);

        $this->actingAs($user)->get(route('sample.next-pending', [
            'sample' => $s1,
            'lab_branch_id' => (string) $branch->id,
        ]))->assertRedirect(route('sample.show', [
            'sample' => $s2,
            'lab_branch_id' => (string) $branch->id,
        ]));
    }
}
