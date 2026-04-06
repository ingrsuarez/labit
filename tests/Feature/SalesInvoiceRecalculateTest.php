<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceRecalculateTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_subtotal_es_neto_gravado_no_suma_totales_con_iva(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'cuit' => '20-11111111-1',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();

        $customer = Customer::query()->create([
            'name' => 'Cliente Test',
            'taxId' => '20-22222222-2',
            'status' => 'activo',
        ]);

        $pos = PointOfSale::query()->create([
            'company_id' => $company->id,
            'code' => '00001',
            'name' => 'PV Test',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        $invoice = SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00000641',
            'voucher_type' => 'A',
            'point_of_sale_id' => $pos->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-01-11',
            'percepciones' => 0,
            'otros_impuestos' => 0,
            'status' => 'pendiente',
            'amount_collected' => 0,
            'created_by' => $user->id,
            'subtotal' => 0,
            'iva_21' => 0,
            'total' => 0,
            'balance' => 0,
        ]);

        $qty = 1;
        $unitPrice = 14605890;
        $ivaRate = 21;
        $ivaAmount = round($qty * $unitPrice * $ivaRate / 100, 2);
        $lineTotal = $qty * $unitPrice + $ivaAmount;

        $invoice->items()->create([
            'description' => 'Analisis Clinicos Laborales',
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'iva_rate' => $ivaRate,
            'iva_amount' => $ivaAmount,
            'total' => $lineTotal,
        ]);

        $invoice->recalculate();
        $invoice->refresh();

        $this->assertSame((float) $unitPrice, (float) $invoice->subtotal);
        $this->assertSame((float) $ivaAmount, (float) $invoice->iva_21);
        $this->assertSame((float) $lineTotal, (float) $invoice->total);
        $this->assertSame((float) $lineTotal, (float) $invoice->balance);
    }
}
