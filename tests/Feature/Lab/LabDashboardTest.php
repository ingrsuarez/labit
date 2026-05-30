<?php

namespace Tests\Feature\Lab;

use App\Models\Admission;
use App\Models\LabBranch;
use App\Models\Sample;
use App\Models\User;
use App\Models\VetAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
        Permission::findOrCreate('lab-admissions.index');
        Permission::findOrCreate('lab-admissions.create');
        Permission::findOrCreate('samples.index');
        foreach (['admin', 'bioquimico', 'tecnico-lab', 'recepcion-lab', 'ventas'] as $name) {
            $role = Role::findOrCreate($name);
            if (in_array($name, ['admin', 'bioquimico', 'tecnico-lab', 'recepcion-lab'])) {
                $role->givePermissionTo(['lab.section', 'lab-admissions.index', 'samples.index']);
            }
        }
    }

    private function labUser(string $role = 'bioquimico'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createBranch(string $name = 'Sede Central'): LabBranch
    {
        return LabBranch::create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private static int $admissionCounter = 1;

    private function createAdmission(array $attrs = []): Admission
    {
        $num = self::$admissionCounter++;

        return Admission::create(array_merge([
            'date' => now(),
            'number' => $num,
            'protocol_number' => 'C-2026-'.str_pad($num, 6, '0', STR_PAD_LEFT),
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

    public function test_usuario_lab_ve_el_dashboard(): void
    {
        $user = $this->labUser();

        $this->actingAs($user)
            ->get('/lab')
            ->assertOk()
            ->assertSee('Dashboard del Laboratorio');
    }

    public function test_kpis_muestran_conteos_correctos(): void
    {
        $branch = $this->createBranch();

        $this->createAdmission([
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        $this->createAdmission([
            'status' => 'validated',
            'lab_branch_id' => $branch->id,
        ]);

        $this->createAdmission([
            'status' => 'validated',
            'sent_at' => now(),
            'lab_branch_id' => $branch->id,
        ]);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'name' => 'Test Customer',
            'taxId' => '20-11111111-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $speciesId = \Illuminate\Support\Facades\DB::table('species')->insertGetId([
            'name' => 'Canino',
            'code' => 'CAN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        VetAdmission::create([
            'date' => now(),
            'protocol_number' => 'V-2026-000001',
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
            'customer_id' => $customerId,
            'species_id' => $speciesId,
            'animal_name' => 'Test',
            'owner_name' => 'Test',
        ]);

        Sample::create([
            'protocol_number' => 'A-2026-000001',
            'entry_date' => now(),
            'sampling_date' => now(),
            'customer_id' => $customerId,
            'location' => 'Test',
            'status' => 'pending',
            'validation_status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        $user = $this->labUser();

        $response = $this->actingAs($user)->get('/lab?branch=all');

        $response->assertOk();
        $response->assertSee('Dashboard del Laboratorio');
    }

    public function test_filtro_por_sede(): void
    {
        $branch1 = $this->createBranch('Sede A');
        $branch2 = $this->createBranch('Sede B');

        $this->createAdmission([
            'status' => 'pending',
            'lab_branch_id' => $branch1->id,
        ]);

        $this->createAdmission([
            'status' => 'pending',
            'lab_branch_id' => $branch2->id,
        ]);

        $user = $this->labUser();

        $response = $this->actingAs($user)
            ->get('/lab?branch='.$branch1->id);

        $response->assertOk()
            ->assertSee('Dashboard del Laboratorio');
    }

    public function test_grafico_por_estado_muestra_labels(): void
    {
        $branch = $this->createBranch();

        $this->createAdmission([
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        $user = $this->labUser();

        $this->actingAs($user)
            ->get('/lab?branch=all')
            ->assertOk()
            ->assertSee('Estado de protocolos')
            ->assertSee('Pendiente')
            ->assertSee('En Proceso')
            ->assertSee('Completado')
            ->assertSee('Validado')
            ->assertSee('Enviado');
    }

    public function test_alerta_de_atrasados_aparece_cuando_hay_protocolos_viejos(): void
    {
        $branch = $this->createBranch();

        $admission = $this->createAdmission([
            'date' => now()->subDays(5),
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        $admission->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();

        $user = $this->labUser();

        $this->actingAs($user)
            ->get('/lab?branch=all')
            ->assertOk()
            ->assertSee('protocolo');
    }

    public function test_sin_alerta_cuando_no_hay_atrasados(): void
    {
        $branch = $this->createBranch();

        $this->createAdmission([
            'status' => 'pending',
            'lab_branch_id' => $branch->id,
        ]);

        $user = $this->labUser();

        $response = $this->actingAs($user)
            ->get('/lab?branch=all');

        $response->assertOk();
        $response->assertDontSee('más de 3 días de antigüedad');
    }

    public function test_bioquimico_ve_home_personalizado_en_dashboard(): void
    {
        $user = $this->labUser('bioquimico');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tus accesos más utilizados');
    }

    public function test_usuario_sin_permiso_lab_recibe_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ventas');

        $this->actingAs($user)
            ->get('/lab')
            ->assertForbidden();
    }
}
