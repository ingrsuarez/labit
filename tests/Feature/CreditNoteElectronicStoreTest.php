<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\AfipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditNoteElectronicStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    public function test_store_electronic_credit_note_commits_before_afip_and_applies_afip_response(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa NC AFIP',
            'cuit' => '30-70000000-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        $customer = Customer::query()->create([
            'name' => 'Cliente NC',
            'tax' => 'Responsable Inscripto',
            'taxId' => '20-22222222-2',
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

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000016',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-01',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'status' => 'confirmada',
            'amount_collected' => 0,
            'created_by' => $user->id,
            'subtotal' => 0,
            'iva_21' => 0,
            'total' => 0,
            'balance' => 0,
            'is_electronic' => true,
            'afip_voucher_number' => 16,
        ]);

        $qty = 1;
        $unitPrice = 46530;
        $ivaRate = 21;
        $ivaAmount = round($qty * $unitPrice * $ivaRate / 100, 2);
        $lineTotal = $qty * $unitPrice + $ivaAmount;
        $invoice->items()->create([
            'description' => 'Analisis Clinicos veterinarios',
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'iva_rate' => $ivaRate,
            'iva_amount' => $ivaAmount,
            'total' => $lineTotal,
            'sort_order' => 0,
        ]);
        $invoice->recalculate();

        $stub = new class($company) extends AfipService
        {
            public function __construct(Company $company)
            {
                parent::__construct($company);
            }

            #[\Override]
            public function createCreditNote(\App\Models\CreditNote $creditNote): array
            {
                return [
                    'cae' => '61423123123123',
                    'cae_expiration' => '20351231',
                    'voucher_number' => 17,
                    'result' => 'A',
                    'observations' => null,
                    'full_response' => ['stub' => true],
                ];
            }
        };
        $this->instance(AfipService::class, $stub);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('credit-notes.store'), [
                'sales_invoice_id' => $invoice->id,
                'reason' => 'error de facturacion',
                'percepciones' => 0,
                'otros_impuestos' => 0,
                'items' => [
                    [
                        'description' => 'Analisis Clinicos veterinarios',
                        'quantity' => 1,
                        'unit_price' => 46530,
                        'iva_rate' => 21,
                    ],
                ],
            ]);

        $cn = CreditNote::query()->where('sales_invoice_id', $invoice->id)->first();
        $this->assertNotNull($cn);
        $response->assertRedirect(route('credit-notes.show', $cn));
        $this->assertSame('confirmada', $cn->fresh()->status);
        $this->assertSame('61423123123123', $cn->fresh()->cae);
        $this->assertSame('00000017', $cn->fresh()->credit_note_number);
    }

    public function test_store_electronic_credit_note_afip_rechazo_persiste_nc_reintentable(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa NC AFIP R',
            'cuit' => '30-70000001-5',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        $customer = Customer::query()->create([
            'name' => 'Cliente NCR',
            'tax' => 'Responsable Inscripto',
            'taxId' => '20-33333333-3',
            'status' => 'activo',
        ]);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00003',
            'name' => 'PdV Elec',
            'is_active' => true,
            'is_electronic' => true,
            'afip_pos_number' => 3,
        ]);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000020',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-05-02',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'status' => 'confirmada',
            'amount_collected' => 0,
            'created_by' => $user->id,
            'subtotal' => 100,
            'iva_21' => 21,
            'total' => 121,
            'balance' => 121,
            'is_electronic' => true,
            'afip_voucher_number' => 20,
        ]);

        $invoice->items()->create([
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 100,
            'iva_rate' => 21,
            'iva_amount' => 21,
            'total' => 121,
            'sort_order' => 0,
        ]);

        $stub = new class($company) extends AfipService
        {
            public function __construct(Company $company)
            {
                parent::__construct($company);
            }

            #[\Override]
            public function createCreditNote(\App\Models\CreditNote $creditNote): array
            {
                return [
                    'cae' => null,
                    'cae_expiration' => null,
                    'voucher_number' => null,
                    'result' => 'R',
                    'observations' => null,
                    'full_response' => [
                        'FeDetResp' => [
                            'FECAEDetResponse' => [
                                'Observaciones' => [
                                    'Obs' => ['Msg' => 'Motivo de rechazo simulado'],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };
        $this->instance(AfipService::class, $stub);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('credit-notes.store'), [
                'sales_invoice_id' => $invoice->id,
                'reason' => 'test',
                'items' => [
                    [
                        'description' => 'Item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'iva_rate' => 21,
                    ],
                ],
            ]);

        $cn = CreditNote::query()->where('sales_invoice_id', $invoice->id)->first();
        $this->assertNotNull($cn);
        $response->assertRedirect(route('credit-notes.show', $cn));
        $response->assertSessionHas('error');
        $this->assertSame('pendiente', $cn->fresh()->status);
        $this->assertNull($cn->fresh()->cae);
    }
}
