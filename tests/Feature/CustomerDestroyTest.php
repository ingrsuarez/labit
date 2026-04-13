<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\Sample;
use App\Models\Species;
use App\Models\User;
use App\Models\VetAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    private function salesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        return $user;
    }

    private function baseCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Cliente Borrable',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
            'type' => ['particular'],
        ]);
    }

    public function test_elimina_cliente_sin_protocolos_ni_facturacion(): void
    {
        $customer = $this->baseCustomer();
        $user = $this->salesUser();

        $response = $this->actingAs($user)
            ->delete(route('customer.destroy', $customer));

        $response->assertRedirect(route('customer.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_no_elimina_si_hay_protocolo_veterinario(): void
    {
        $customer = $this->baseCustomer();
        $species = Species::query()->create([
            'name' => 'Canino',
            'code' => 'CAN',
            'is_active' => true,
        ]);

        VetAdmission::query()->create([
            'protocol_number' => 'VET-DEL-1',
            'date' => '2026-04-13',
            'customer_id' => $customer->id,
            'veterinarian_id' => null,
            'species_id' => $species->id,
            'animal_name' => 'Luna',
            'owner_name' => 'Ana',
            'status' => 'pending',
            'total_price' => 0,
        ]);

        $user = $this->salesUser();

        $response = $this->actingAs($user)
            ->from(route('customer.edit', $customer))
            ->delete(route('customer.destroy', $customer));

        $response->assertRedirect(route('customer.edit', $customer));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('protocolos cargados', session('error'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_no_elimina_si_hay_muestra_aguas(): void
    {
        $customer = $this->baseCustomer();

        Sample::query()->create([
            'protocol_number' => 'A-999001',
            'sample_type' => 'agua',
            'entry_date' => '2026-04-13',
            'sampling_date' => '2026-04-12',
            'customer_id' => $customer->id,
            'location' => 'Pozo 1',
            'status' => 'pending',
        ]);

        $user = $this->salesUser();

        $response = $this->actingAs($user)
            ->from(route('customer.edit', $customer))
            ->delete(route('customer.destroy', $customer));

        $response->assertRedirect(route('customer.edit', $customer));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('protocolos cargados', session('error'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_no_elimina_si_hay_factura_de_venta(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'cuit' => '20-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $customer = $this->baseCustomer();
        $user = $this->salesUser();

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00001',
            'name' => 'PV Test',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000001',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-04-13',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'status' => 'pendiente',
            'amount_collected' => 0,
            'created_by' => $user->id,
            'subtotal' => 100,
            'iva_21' => 21,
            'total' => 121,
            'balance' => 121,
        ]);

        $response = $this->actingAs($user)
            ->from(route('customer.edit', $customer))
            ->delete(route('customer.destroy', $customer));

        $response->assertRedirect(route('customer.edit', $customer));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('facturación registrada', session('error'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
