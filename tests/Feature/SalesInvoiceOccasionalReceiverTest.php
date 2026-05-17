<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalesInvoiceOccasionalReceiverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ventas.section', 'sales-invoices.create'] as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function test_store_factura_b_with_occasional_receiver_persists_snapshot(): void
    {
        [$user, $pos] = $this->actingUserWithPos();

        $response = $this->actingAs($user)->withSession(['active_company_id' => $pos->company_id])->post(route('sales-invoices.store'), [
            'invoice_number' => '00000999',
            'voucher_type' => 'B',
            'point_of_sale_id' => $pos->id,
            'receiver_mode' => 'occasional',
            'receiver_name' => 'Juan Pérez',
            'receiver_tax_condition' => 'consumidor final',
            'receiver_document_number' => '',
            'issue_date' => '2026-05-17',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'items' => [
                [
                    'description' => 'Servicio de análisis',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'iva_rate' => 21,
                ],
            ],
        ]);

        $response->assertRedirect();
        $invoice = SalesInvoice::query()->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->customer_id);
        $this->assertSame('Juan Pérez', $invoice->receiver_name);
        $this->assertSame('consumidor final', $invoice->receiver_tax_condition);
        $this->assertSame(99, $invoice->receiverDocTipo());
        $this->assertSame(0, $invoice->receiverDocNro());
    }

    public function test_store_factura_b_with_customer_still_works(): void
    {
        [$user, $pos] = $this->actingUserWithPos();
        $customer = Customer::query()->create([
            'name' => 'Cliente FC B',
            'tax' => 'Consumidor Final',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
        ]);

        $response = $this->actingAs($user)->withSession(['active_company_id' => $pos->company_id])->post(route('sales-invoices.store'), [
            'invoice_number' => '00001000',
            'voucher_type' => 'B',
            'point_of_sale_id' => $pos->id,
            'receiver_mode' => 'customer',
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-17',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'items' => [
                [
                    'description' => 'Servicio',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'iva_rate' => 21,
                ],
            ],
        ]);

        $response->assertRedirect();
        $invoice = SalesInvoice::query()->latest('id')->first();
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertNull($invoice->receiver_name);
    }

    public function test_store_factura_a_without_customer_fails_validation(): void
    {
        [$user, $pos] = $this->actingUserWithPos();

        $response = $this->actingAs($user)->withSession(['active_company_id' => $pos->company_id])->post(route('sales-invoices.store'), [
            'invoice_number' => '00001001',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'receiver_mode' => 'occasional',
            'receiver_name' => 'Receptor inválido',
            'receiver_tax_condition' => 'consumidor final',
            'issue_date' => '2026-05-17',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'items' => [
                [
                    'description' => 'Servicio',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'iva_rate' => 21,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('sales_invoices', 0);
    }

    /**
     * @return array{0: User, 1: PointOfSale}
     */
    private function actingUserWithPos(): array
    {
        $company = Company::query()->create([
            'name' => 'Empresa FC B ocasional',
            'cuit' => '30-71111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['is_default' => true]);
        $user->givePermissionTo(['ventas.section', 'sales-invoices.create']);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00099',
            'name' => 'PV Test',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        return [$user, $pos];
    }
}
