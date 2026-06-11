<?php

namespace Tests\Feature;

use App\Mail\SampleBatchMail;
use App\Models\Customer;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleBatchEmailDesyncTest extends TestCase
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

    public function test_batch_envia_muestra_validada_por_calculated_status_aunque_validation_status_pendiente(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Desync',
            'taxId' => '30-99999999-9',
            'email' => 'cliente@desync.test',
            'status' => 'activo',
            'type' => ['comun'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-DESYNC-001',
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => 'pending',
            'created_by' => $user->id,
        ]);

        $test = Test::query()->create([
            'code' => 'DESYNC1',
            'name' => 'Test desync',
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

        $sample->load('determinations');

        $this->assertTrue($sample->isValidated());

        $response = $this->actingAs($user)->postJson(route('sample.batch-email'), [
            'sample_ids' => [$sample->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['sent' => ['A-DESYNC-001']]);

        Mail::assertSent(SampleBatchMail::class);
    }

    public function test_show_con_open_email_inicializa_modal_visible(): void
    {
        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples.show', 'samples-reports.send']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Modal',
            'taxId' => '30-88888888-8',
            'email' => 'modal@cliente.test',
            'status' => 'activo',
            'type' => ['comun'],
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => 'A-OPEN-001',
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Tanque',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => 'validated',
            'created_by' => $user->id,
        ]);

        $test = Test::query()->create([
            'code' => 'OPEN1',
            'name' => 'Test open email',
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

        $response = $this->actingAs($user)->get(route('sample.show', [
            'sample' => $sample,
            'open_email' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('showEmailModal: true', false);
        $response->assertSee('Enviar resultados por email', false);
        $response->assertSee('modal@cliente.test', false);
    }
}
