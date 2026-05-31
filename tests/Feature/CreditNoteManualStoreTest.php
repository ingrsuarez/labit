<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditNoteManualStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('ventas.section');
    }

    public function test_create_manual_includes_electronic_points_of_sale(): void
    {
        $company = Company::query()->create([
            'name' => 'Olie Clara Silvina',
            'cuit' => '30-70000000-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('ventas.section');

        PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00006',
            'name' => 'webservices',
            'is_active' => true,
            'is_electronic' => false,
            'afip_pos_number' => 6,
        ]);

        $electronicPos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00007',
            'name' => 'Central',
            'is_active' => true,
            'is_electronic' => true,
            'afip_pos_number' => 7,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('credit-notes.create-manual'));

        $response->assertOk();
        $response->assertSee('00007 - Central', false);
    }

    public function test_store_manual_accepts_electronic_point_of_sale_without_afip(): void
    {
        $company = Company::query()->create([
            'name' => 'Olie Clara Silvina',
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

        $electronicPos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00007',
            'name' => 'Central',
            'is_active' => true,
            'is_electronic' => true,
            'afip_pos_number' => 7,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('credit-notes.store-manual'), [
                'customer_id' => $customer->id,
                'voucher_type' => 'B',
                'point_of_sale_id' => $electronicPos->id,
                'credit_note_number' => '00000012',
                'issue_date' => '2026-05-31',
                'reason' => 'Devolucion parcial',
                'percepciones' => 0,
                'otros_impuestos' => 0,
                'items' => [
                    [
                        'description' => 'Servicio',
                        'quantity' => 1,
                        'unit_price' => 1000,
                        'iva_rate' => 21,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $creditNote = CreditNote::query()->first();
        $this->assertNotNull($creditNote);
        $this->assertSame($electronicPos->id, $creditNote->point_of_sale_id);
        $this->assertFalse($creditNote->is_electronic);
        $this->assertSame('confirmada', $creditNote->status);
        $this->assertNull($creditNote->cae);
    }
}
