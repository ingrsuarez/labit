<?php

namespace Tests\Feature;

use App\Contracts\SantaCruzFtpClientInterface;
use App\Models\Admission;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\SantaCruzTestMapping;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FakeSantaCruzFtp implements SantaCruzFtpClientInterface
{
    /** @var array<string, string> */
    public array $files = [];

    /** @var list<string> */
    public array $moved = [];

    public function listXmlFiles(): array
    {
        return array_keys($this->files);
    }

    public function getFileContents(string $basename): string
    {
        return $this->files[$basename] ?? '';
    }

    public function moveToProcessed(string $basename): void
    {
        $this->moved[] = $basename;
    }
}

class SantaCruzImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('lab.section');
        Permission::findOrCreate('santacruz.import');
        Permission::findOrCreate('lab-admissions.index');
        $role = Role::findOrCreate('recepcion-lab');
        $role->givePermissionTo(['lab.section', 'santacruz.import', 'lab-admissions.index']);
    }

    public function test_store_mapping_normalizes_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');

        $test = Test::create([
            'name' => 'Glucemia',
            'code' => '0412',
        ]);

        $this->actingAs($user)->post(route('lab.santa-cruz.mappings.store'), [
            'prestacion_code' => '08.05.01',
            'prestacion_name' => 'Colesterol LDL',
            'test_id' => $test->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('santa_cruz_test_mappings', [
            'prestacion_code' => '080501',
            'test_id' => $test->id,
        ]);
    }

    public function test_import_creates_admission_and_moves_file(): void
    {
        config(['santacruz.insurance_id' => null]);

        $insurance = Insurance::create([
            'name' => 'Santa Cruz Test OS',
            'type' => 'obra_social',
            'nbu_value' => 100,
        ]);
        config(['santacruz.insurance_id' => $insurance->id]);

        $test = Test::create([
            'name' => 'Hidroxipireno ART',
            'code' => '0682',
            'nbu' => 1,
        ]);

        InsuranceTest::create([
            'insurance_id' => $insurance->id,
            'test_id' => $test->id,
            'nbu_units' => 1,
            'price' => 0,
            'requires_authorization' => false,
            'copago' => 0,
        ]);

        $xml = file_get_contents(base_path('tests/Fixtures/santacruz/sample.xml'));
        $fake = new FakeSantaCruzFtp;
        $fake->files['demo.xml'] = $xml;
        $this->app->instance(SantaCruzFtpClientInterface::class, $fake);

        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');

        $this->actingAs($user)->post(route('lab.santa-cruz.sync.scan'))->assertRedirect(route('lab.santa-cruz.sync'));

        $this->actingAs($user)->post(route('lab.santa-cruz.sync.import'), [
            'files' => ['demo.xml'],
        ])->assertRedirect(route('lab.santa-cruz.sync'));

        $this->assertDatabaseHas('admissions', [
            'affiliate_number' => 'lcm222335',
            'insurance' => $insurance->id,
        ]);

        $admission = Admission::query()->where('affiliate_number', 'lcm222335')->first();
        $this->assertNotNull($admission);
        $this->assertTrue($admission->admissionTests()->where('test_id', $test->id)->exists());
        $this->assertSame(['demo.xml'], $fake->moved);
    }

    public function test_mapping_table_used_before_test_code_match(): void
    {
        $insurance = Insurance::create([
            'name' => 'Santa Cruz Test OS',
            'type' => 'obra_social',
            'nbu_value' => 50,
        ]);
        config(['santacruz.insurance_id' => $insurance->id]);

        $tA = Test::create(['name' => 'Por mapeo', 'code' => 'X999', 'nbu' => 1]);
        $tB = Test::create(['name' => 'Por código', 'code' => '0682', 'nbu' => 1]);
        foreach ([$tA, $tB] as $t) {
            InsuranceTest::create([
                'insurance_id' => $insurance->id,
                'test_id' => $t->id,
                'nbu_units' => 1,
                'price' => 10,
                'requires_authorization' => false,
                'copago' => 0,
            ]);
        }

        SantaCruzTestMapping::create([
            'prestacion_code' => SantaCruzTestMapping::normalizePrestacionCode('0682'),
            'prestacion_name' => 'Alias',
            'test_id' => $tA->id,
        ]);

        $xml = file_get_contents(base_path('tests/Fixtures/santacruz/sample.xml'));
        $fake = new FakeSantaCruzFtp;
        $fake->files['x.xml'] = $xml;
        $this->app->instance(SantaCruzFtpClientInterface::class, $fake);

        $user = User::factory()->create();
        $user->assignRole('recepcion-lab');

        $this->actingAs($user)->post(route('lab.santa-cruz.sync.scan'));
        $this->actingAs($user)->post(route('lab.santa-cruz.sync.import'), ['files' => ['x.xml']]);

        $admission = Admission::query()->where('affiliate_number', 'lcm222335')->first();
        $this->assertNotNull($admission);
        $this->assertTrue($admission->admissionTests()->where('test_id', $tA->id)->exists());
        $this->assertFalse($admission->admissionTests()->where('test_id', $tB->id)->exists());
    }
}
