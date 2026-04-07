<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackfillSalesInvoiceJournalEntriesTest extends TestCase
{
    use RefreshDatabase;

    private function seedSalesJournalAccounts(): void
    {
        foreach ([
            ['1.1.04', 'Deudores por Ventas', 'activo'],
            ['4.1.01', 'Ventas', 'resultado_positivo'],
        ] as [$code, $name, $type]) {
            AccountingAccount::query()->create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => null,
                'level' => 3,
                'is_header' => false,
                'is_active' => true,
            ]);
        }
    }

    private function makeInvoiceWithoutJournal(): SalesInvoice
    {
        $company = Company::query()->create([
            'name' => 'Empresa BF',
            'cuit' => '20-77777777-7',
            'tax_condition' => 'responsable_inscripto',
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['is_default' => true]);

        $customer = Customer::query()->create([
            'name' => 'Cliente BF',
            'taxId' => '20-88888888-8',
            'status' => 'activo',
        ]);

        return SalesInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => '00099',
            'voucher_type' => 'B',
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'iva_21' => 0,
            'iva_10_5' => 0,
            'iva_27' => 0,
            'total' => 100,
            'amount_collected' => 0,
            'balance' => 100,
            'status' => 'pendiente',
            'is_electronic' => false,
            'created_by' => $user->id,
        ]);
    }

    public function test_backfill_creates_missing_journal_entry(): void
    {
        $this->seedSalesJournalAccounts();
        $this->makeInvoiceWithoutJournal();

        $this->assertSame(0, JournalEntry::query()->count());

        Artisan::call('accounting:backfill-sales-journal');

        $this->assertSame(1, JournalEntry::query()->count());
        $this->assertTrue(
            JournalEntry::query()
                ->where('source_type', SalesInvoice::class)
                ->exists()
        );
    }

    public function test_dry_run_does_not_create_entries(): void
    {
        $this->seedSalesJournalAccounts();
        $this->makeInvoiceWithoutJournal();

        Artisan::call('accounting:backfill-sales-journal', ['--dry-run' => true]);

        $this->assertSame(0, JournalEntry::query()->count());
    }

    public function test_second_run_is_noop_when_already_has_entry(): void
    {
        $this->seedSalesJournalAccounts();
        $this->makeInvoiceWithoutJournal();

        Artisan::call('accounting:backfill-sales-journal');
        Artisan::call('accounting:backfill-sales-journal');

        $this->assertSame(1, JournalEntry::query()->count());
    }
}
