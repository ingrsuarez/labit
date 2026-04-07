<?php

namespace App\Console\Commands;

use App\Models\SalesInvoice;
use App\Services\AccountingEntryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSalesInvoiceJournalEntries extends Command
{
    protected $signature = 'accounting:backfill-sales-journal
                            {--company= : Filtrar por company_id}
                            {--dry-run : Listar facturas sin asiento, sin crear nada}';

    protected $description = 'Crea asientos automáticos del libro diario para facturas de venta que aún no tienen asiento';

    public function handle(AccountingEntryService $accounting): int
    {
        $dry = (bool) $this->option('dry-run');
        $companyOpt = $this->option('company');

        $query = SalesInvoice::query()
            ->where('status', '!=', 'anulada')
            ->where(function ($q) {
                $q->where('is_electronic', false)
                    ->orWhereNotNull('cae');
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('journal_entries')
                    ->whereColumn('journal_entries.source_id', 'sales_invoices.id')
                    ->where('journal_entries.source_type', SalesInvoice::class);
            })
            ->orderBy('id');

        if ($companyOpt !== null && $companyOpt !== '') {
            $query->where('company_id', (int) $companyOpt);
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->info('No hay facturas de venta pendientes de asiento (con los filtros aplicados).');

            return self::SUCCESS;
        }

        $this->info($dry ? "Se encontraron {$count} factura(s) sin asiento (dry-run)." : "Procesando {$count} factura(s)…");

        $ok = 0;
        $failed = 0;

        $query->with(['customer', 'pointOfSale', 'items'])->chunkById(50, function ($invoices) use ($accounting, $dry, &$ok, &$failed) {
            foreach ($invoices as $invoice) {
                $label = "#{$invoice->id} {$invoice->full_number} (empresa {$invoice->company_id})";

                if ($dry) {
                    $this->line("  - {$label}");

                    continue;
                }

                $entry = $accounting->fromSalesInvoice($invoice);
                if ($entry !== null) {
                    $this->info("  Asiento creado: {$label} → journal_entries.id={$entry->id}");
                    $ok++;
                } else {
                    $this->warn("  Sin asiento (plan de cuentas o totales): {$label} — revisá storage/logs");
                    $failed++;
                }
            }
        });

        if (! $dry) {
            $this->newLine();
            $this->info("Listo: {$ok} creado(s), {$failed} omitido(s).");
        }

        return $failed > 0 && ! $dry ? self::FAILURE : self::SUCCESS;
    }
}
