<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Insurance;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProtocolNumberUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
        Permission::findOrCreate('lab-admissions.create');
    }

    public function test_dos_admisiones_secuenciales_tienen_protocol_number_distintos_y_consecutivos(): void
    {
        $todayPrefix = 'C'.now()->format('ymd');
        $this->seedAdmissionWithProtocol($todayPrefix.'0099');

        $first = $this->createAdmissionWithGeneratedProtocol();
        $second = $this->createAdmissionWithGeneratedProtocol();

        $this->assertNotSame($first, $second);
        $this->assertSame($todayPrefix.'0100', $first);
        $this->assertSame($todayPrefix.'0101', $second);
    }

    public function test_unique_en_db_rechaza_protocol_number_duplicado(): void
    {
        $protocol = 'C'.now()->format('ymd').'7777';

        $this->seedAdmissionWithProtocol($protocol);

        $this->expectException(QueryException::class);

        $this->seedAdmissionWithProtocol($protocol);
    }

    public function test_store_clinico_no_deja_duplicado_cuando_ya_existe_el_ultimo_numero(): void
    {
        $user = $this->userWithAdmissionCreate();
        [$patient, $insurance, $test] = $this->clinicalFixtures();

        $todayPrefix = 'C'.now()->format('ymd');
        $occupied = $todayPrefix.'0200';
        $this->seedAdmissionWithProtocol($occupied);

        $response = $this->actingAs($user)->post(route('lab.admissions.store'), [
            'patient_id' => $patient->id,
            'date' => now()->toDateString(),
            'insurance_id' => $insurance->id,
            'tests' => [[
                'test_id' => $test->id,
                'price' => 100,
                'authorization_status' => 'not_required',
                'paid_by_patient' => false,
                'copago' => 0,
            ]],
        ]);

        $response->assertRedirect();

        $created = Admission::query()
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($created);
        $this->assertSame($todayPrefix.'0201', $created->protocol_number);
        $this->assertSame(1, Admission::query()->where('protocol_number', $created->protocol_number)->count());
    }

    public function test_formato_protocol_number_clinico(): void
    {
        $number = $this->generateProtocolInTransaction();

        $this->assertMatchesRegularExpression('/^C\d{10}$/', $number);
    }

    private function generateProtocolInTransaction(): string
    {
        return DB::transaction(fn () => Admission::generateProtocolNumber());
    }

    private function createAdmissionWithGeneratedProtocol(): string
    {
        return DB::transaction(function () {
            $protocolNumber = Admission::generateProtocolNumber();
            $this->seedAdmissionWithProtocol($protocolNumber);

            return $protocolNumber;
        });
    }

    private function seedAdmissionWithProtocol(string $protocolNumber): Admission
    {
        static $n = 9000;

        return Admission::query()->create([
            'date' => now(),
            'number' => $n++,
            'protocol_number' => $protocolNumber,
            'status' => 'pending',
            'room' => 1,
            'institution' => 1,
            'invoice_date' => now(),
            'promise_date' => now(),
            'authorization_code' => '',
            'attended_by' => 0,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => 0,
        ]);
    }

    private function userWithAdmissionCreate(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lab.section', 'lab-admissions.create']);

        return $user;
    }

    /**
     * @return array{0: Patient, 1: Insurance, 2: Test}
     */
    private function clinicalFixtures(): array
    {
        $insurance = Insurance::query()->create([
            'name' => 'OS protocol uniqueness',
            'type' => 'obra_social',
            'nbu_value' => 100,
        ]);

        $patient = Patient::query()->create([
            'name' => 'juan',
            'lastName' => 'perez',
            'patientId' => '30111222',
            'sex' => 'M',
            'insurance' => $insurance->id,
            'type' => 'active',
        ]);

        $test = Test::query()->create([
            'code' => 'PROTUNQ1',
            'name' => 'Practica uniqueness',
            'nbu' => 1,
            'price' => 100,
            'categories' => ['clinico'],
        ]);

        return [$patient, $insurance, $test];
    }
}
