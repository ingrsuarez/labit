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
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleBatchEmailTest extends TestCase
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

    private function makeValidatedSample(Customer $customer, User $user, string $protocolNumber): Sample
    {
        $sample = Sample::query()->create([
            'protocol_number' => $protocolNumber,
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
            'code' => 'BAT'.$protocolNumber,
            'name' => 'Test batch',
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

        return $sample;
    }

    public function test_invitado_no_puede_envio_masivo_muestras(): void
    {
        $this->postJson(route('sample.batch-email'), [
            'sample_ids' => [1],
        ])->assertUnauthorized();
    }

    public function test_usuario_sin_permiso_muestras_recibe_403(): void
    {
        Role::create(['name' => 'test_sin_muestras', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('test_sin_muestras');

        $this->actingAs($user)
            ->postJson(route('sample.batch-email'), [
                'sample_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_batch_email_envia_mail_y_marca_sent_at(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grant($user, ['samples.section', 'samples-reports.send']);

        $customer = Customer::query()->create([
            'name' => 'Cliente Batch',
            'taxId' => '20-44444444-4',
            'status' => 'activo',
            'type' => 'particular',
            'email' => 'cliente@batch.test',
        ]);

        $s1 = $this->makeValidatedSample($customer, $user, 'A-2026-BAT001');
        $s2 = $this->makeValidatedSample($customer, $user, 'A-2026-BAT002');

        $response = $this->actingAs($user)->postJson(route('sample.batch-email'), [
            'sample_ids' => [$s1->id, $s2->id],
            'email_overrides' => [
                (string) $customer->id => 'destino@example.com',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'sent' => ['A-2026-BAT001', 'A-2026-BAT002'],
        ]);

        Mail::assertSent(SampleBatchMail::class);

        $s1->refresh();
        $s2->refresh();
        $this->assertNotNull($s1->sent_at);
        $this->assertNotNull($s2->sent_at);
    }
}
