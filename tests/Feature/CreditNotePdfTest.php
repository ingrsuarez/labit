<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditNotePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
        Permission::findOrCreate('sales-invoices.index');
    }

    public function test_confirmed_credit_note_pdf_downloads(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa PDF NC',
            'cuit' => '30-70000002-3',
            'tax_condition' => 'responsable_inscripto',
            'address' => 'Calle 1',
            'city' => 'Neuquén',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['ventas.section', 'sales-invoices.index']);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente PDF',
            'tax' => 'Responsable Inscripto',
            'taxId' => '20-44444444-4',
            'status' => 'activo',
        ]);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00002',
            'name' => 'Leguizamon',
            'is_active' => true,
            'is_electronic' => true,
            'afip_pos_number' => 2,
        ]);

        $creditNote = CreditNote::query()->create([
            'company_id' => $company->id,
            'credit_note_number' => '00000001',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-17',
            'reason' => 'Error de facturacion',
            'subtotal' => 46530,
            'iva_21' => 9771.30,
            'total' => 56301.30,
            'status' => 'confirmada',
            'is_electronic' => true,
            'cae' => '86205892297905',
            'cae_expiration' => '2026-05-27',
            'afip_voucher_number' => 1,
            'afip_result' => 'A',
            'created_by' => $user->id,
        ]);

        CreditNoteItem::query()->create([
            'credit_note_id' => $creditNote->id,
            'description' => 'Analisis Clinicos',
            'quantity' => 1,
            'unit_price' => 46530,
            'iva_rate' => 21,
            'iva_amount' => 9771.30,
            'total' => 56301.30,
            'sort_order' => 0,
        ]);

        ob_start();
        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('credit-notes.pdf', $creditNote));
        $streamed = ob_get_clean();

        $response->assertOk();
        $body = $response->getContent() ?: $streamed;
        $this->assertStringStartsWith('%PDF', $body);
    }

    public function test_pending_credit_note_pdf_returns_redirect(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa NC pendiente',
            'cuit' => '30-70000003-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['ventas.section', 'sales-invoices.index']);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente',
            'taxId' => '20-55555555-5',
            'status' => 'activo',
        ]);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00002',
            'name' => 'PdV',
            'is_active' => true,
            'is_electronic' => true,
            'afip_pos_number' => 2,
        ]);

        $creditNote = CreditNote::query()->create([
            'company_id' => $company->id,
            'credit_note_number' => 'PENDIENTE-AFIP',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'sales_invoice_id' => null,
            'issue_date' => '2026-05-17',
            'reason' => 'test',
            'subtotal' => 0,
            'total' => 0,
            'status' => 'pendiente',
            'is_electronic' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('credit-notes.pdf', $creditNote));

        $response->assertRedirect(route('credit-notes.show', $creditNote));
        $response->assertSessionHas('error');
    }
}
