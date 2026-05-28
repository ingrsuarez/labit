<?php

namespace Tests\Feature;

use App\Mail\SampleResultMail;
use App\Models\Customer;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleSendEmailTest extends TestCase
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

    private function makeTestModel(string $code = 'SEND1'): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'Determinación email',
            'unit' => 'mg/dL',
            'price' => 100,
            'categories' => ['aguas_alimentos'],
        ]);
    }

    private function makeSampleWithDeterminations(User $user, bool $validated = true, bool $secondValidated = false): Sample
    {
        $customer = Customer::query()->create([
            'name' => 'Cliente Muestras',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
            'type' => 'particular',
            'email' => 'cliente@muestras.test',
        ]);

        $sample = Sample::query()->create([
            'protocol_number' => Sample::generateProtocolNumber(),
            'sample_type' => 'agua',
            'entry_date' => today()->toDateString(),
            'sampling_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Planta',
            'batch' => 'L1',
            'product_name' => 'Agua',
            'status' => 'completed',
            'validation_status' => $validated && ! $secondValidated ? 'pending' : 'partially_validated',
            'created_by' => $user->id,
        ]);

        $test1 = $this->makeTestModel('SEND1');
        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test1->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => $validated,
            'validated_by' => $validated ? $user->id : null,
        ]);

        $test2 = $this->makeTestModel('SEND2');
        SampleDetermination::query()->create([
            'sample_id' => $sample->id,
            'test_id' => $test2->id,
            'price' => 100,
            'status' => 'completed',
            'is_validated' => $secondValidated,
            'validated_by' => $secondValidated ? $user->id : null,
        ]);

        return $sample->fresh(['customer', 'determinations']);
    }

    public function test_usuario_con_permiso_puede_enviar_con_una_determinacion_validada(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $sample = $this->makeSampleWithDeterminations($user, validated: true, secondValidated: false);

        $response = $this->actingAs($user)->post(route('sample.sendEmail', $sample), [
            'email' => 'destino@example.com',
            'message' => 'Mensaje opcional',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $sample->refresh();
        $this->assertNotNull($sample->sent_at);

        Mail::assertSent(SampleResultMail::class, function (SampleResultMail $mail) use ($sample) {
            return $mail->sample->is($sample);
        });
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        Role::create(['name' => 'test_sin_send_muestras', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('test_sin_send_muestras');
        Permission::findOrCreate('samples.section', 'web');
        $user->givePermissionTo('samples.section');

        $sample = $this->makeSampleWithDeterminations($user, validated: true);

        $this->actingAs($user)
            ->post(route('sample.sendEmail', $sample), [
                'email' => 'destino@example.com',
            ])
            ->assertForbidden();
    }

    public function test_protocolo_sin_determinaciones_validadas_muestra_error(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $sample = $this->makeSampleWithDeterminations($user, validated: false, secondValidated: false);

        $response = $this->actingAs($user)->post(route('sample.sendEmail', $sample), [
            'email' => 'destino@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Debe validar al menos una determinación para enviar el informe.');

        $sample->refresh();
        $this->assertNull($sample->sent_at);
        Mail::assertNothingSent();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $user = User::factory()->create();
        $sample = $this->makeSampleWithDeterminations($user, validated: true);

        $this->post(route('sample.sendEmail', $sample), [
            'email' => 'destino@example.com',
        ])->assertRedirect();
    }
}
