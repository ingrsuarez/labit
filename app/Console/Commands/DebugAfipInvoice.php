<?php

namespace App\Console\Commands;

use App\Models\SalesInvoice;
use Illuminate\Console\Command;

/**
 * Comando de debug para inspeccionar una factura electronica que fue rechazada
 * por AFIP. Imprime los datos del comprobante, cliente, items y el afip_response
 * con los Errors / Observaciones que devolvio AFIP en el ultimo intento.
 *
 * Uso:
 *   php artisan afip:debug-invoice 33
 */
class DebugAfipInvoice extends Command
{
    protected $signature = 'afip:debug-invoice {id : ID de la factura a inspeccionar}';

    protected $description = 'Inspecciona una factura electronica y muestra el ultimo afip_response (Errors / Observaciones)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');

        $f = SalesInvoice::with(['customer', 'pointOfSale', 'items', 'company'])->find($id);

        if (! $f) {
            $this->error("No existe SalesInvoice id={$id}");

            return self::FAILURE;
        }

        $this->info('=== FACTURA '.$f->id.' ===');
        $this->line('company_id:    '.$f->company_id.' ('.($f->company?->cuit ?? 'sin company').' / '.($f->company?->name ?? '').')');
        $this->line('voucher_type:  '.$f->voucher_type);
        $this->line('is_electronic: '.($f->is_electronic ? 'SI' : 'NO'));
        $this->line('cae:           '.($f->cae ?? 'NULL'));
        $this->line('cae_exp:       '.($f->cae_expiration ?? 'NULL'));
        $this->line('afip_result:   '.($f->afip_result ?? 'NULL'));
        $this->line('invoice_number:'.($f->invoice_number ?? 'NULL'));
        $this->line('voucher_num:   '.($f->afip_voucher_number ?? 'NULL'));
        $this->line('issue_date:    '.($f->issue_date?->format('Y-m-d') ?? 'NULL'));
        $this->line('due_date:      '.($f->due_date?->format('Y-m-d') ?? 'NULL'));
        $this->line('subtotal:      '.$f->subtotal);
        $this->line('iva:           '.$f->iva);
        $this->line('total:         '.$f->total);

        $this->newLine();
        $this->info('=== CLIENTE ===');
        if ($f->customer) {
            $this->line('id:    '.$f->customer->id);
            $this->line('name:  '.$f->customer->name);
            $this->line('taxId: '.($f->customer->taxId ?? 'NULL'));
            $this->line('tax:   '.($f->customer->tax ?? 'NULL'));
        } else {
            $this->line('SIN CLIENTE');
        }

        $this->newLine();
        $this->info('=== PUNTO DE VENTA ===');
        if ($f->pointOfSale) {
            $this->line('id:              '.$f->pointOfSale->id);
            $this->line('code:            '.$f->pointOfSale->code);
            $this->line('name:            '.$f->pointOfSale->name);
            $this->line('afip_pos_number: '.$f->pointOfSale->afip_pos_number);
            $this->line('is_electronic:   '.($f->pointOfSale->is_electronic ? 'SI' : 'NO'));
            $this->line('company_id:      '.$f->pointOfSale->company_id);
        } else {
            $this->line('SIN POS');
        }

        $this->newLine();
        $this->info('=== ITEMS ('.$f->items->count().') ===');
        foreach ($f->items as $it) {
            $this->line(sprintf(
                '  id=%d  qty=%s  price=%s  iva_rate=%s  iva_amount=%s  subtotal=%s',
                $it->id, $it->quantity, $it->unit_price, $it->iva_rate, $it->iva_amount, $it->subtotal ?? ''
            ));
        }

        $this->newLine();
        $this->info('=== AFIP RESPONSE (ultimo intento) ===');
        if ($f->afip_response) {
            $resp = is_array($f->afip_response) ? $f->afip_response : json_decode(json_encode($f->afip_response), true);
            $this->line(json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->warn('SIN RESPUESTA AFIP TODAVIA');
        }

        return self::SUCCESS;
    }
}
