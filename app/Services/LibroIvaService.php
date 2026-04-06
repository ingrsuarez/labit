<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Generación de archivos TXT Libro IVA Digital (RG 4597).
 *
 * Nota: las retenciones de IVA sufridas en cobranzas (RC confirmados) no se incluyen en estos TXT;
 * ver resumen en Libro IVA → preview del período.
 */
class LibroIvaService
{
    const VOUCHER_TYPES_VENTAS = [
        'Factura A' => '001', 'Nota de Débito A' => '002', 'Nota de Crédito A' => '003',
        'Factura B' => '006', 'Nota de Débito B' => '007', 'Nota de Crédito B' => '008',
        'Factura C' => '011', 'Nota de Débito C' => '012', 'Nota de Crédito C' => '013',
    ];

    const DOC_CUIT = '80';

    const DOC_DNI = '96';

    const DOC_SIN_IDENTIFICAR = '99';

    const ALICUOTAS = [
        '0' => '0003',
        '10.5' => '0004',
        '21' => '0005',
        '27' => '0006',
        '5' => '0008',
        '2.5' => '0009',
    ];

    const MONEDA_PESOS = 'PES';

    const TIPO_CAMBIO_DEFAULT = '0001000000';

    public function generate(int $companyId, int $year, int $month): array
    {
        return [
            'LIBRO_IVA_DIGITAL_VENTAS_CBTE' => $this->generateVentasCabecera($companyId, $year, $month),
            'LIBRO_IVA_DIGITAL_VENTAS_ALICUOTAS' => $this->generateVentasAlicuotas($companyId, $year, $month),
            'LIBRO_IVA_DIGITAL_COMPRAS_CBTE' => $this->generateComprasCabecera($companyId, $year, $month),
            'LIBRO_IVA_DIGITAL_COMPRAS_ALICUOTAS' => $this->generateComprasAlicuotas($companyId, $year, $month),
        ];
    }

    // ─── VENTAS CABECERA (266 chars) ─────────────────────────────

    public function generateVentasCabecera(int $companyId, int $year, int $month): string
    {
        $lines = [];

        $invoices = SalesInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->with('customer', 'pointOfSale')
            ->get();

        foreach ($invoices as $inv) {
            $lines[] = $this->buildVentaCabeceraLine($inv);
        }

        $creditNotes = CreditNote::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->with('customer', 'pointOfSale')
            ->get();

        foreach ($creditNotes as $cn) {
            $lines[] = $this->buildVentaCabeceraLine($cn, true);
        }

        $this->validateLineLength($lines, 266, 'ventas_cbte');

        return implode("\r\n", $lines);
    }

    private function buildVentaCabeceraLine($doc, bool $isCreditNote = false): string
    {
        $customer = $doc->customer;
        $tipoCbte = $this->getVentaVoucherTypeCode($doc->voucher_type, $isCreditNote);
        $tipoDoc = $this->getDocType($customer);
        $nroDoc = $this->getDocNumber($customer);
        $pv = $doc->pointOfSale
            ? $doc->pointOfSale->code
            : ($doc->point_of_sale ?? '0');
        $nroCbte = $doc->afip_voucher_number ?? $doc->invoice_number ?? $doc->credit_note_number ?? '0';
        $cantAlicuotas = $this->countAlicuotas($doc);

        $line = '';
        $line .= $this->formatDate($doc->issue_date);
        $line .= $this->formatNumeric($tipoCbte, 3);
        $line .= $this->formatNumeric($pv, 5);
        $line .= $this->formatNumeric($nroCbte, 20);
        $line .= $this->formatNumeric($nroCbte, 20);
        $line .= $this->formatNumeric($tipoDoc, 2);
        $line .= $this->formatNumeric($nroDoc, 20);
        $line .= $this->formatText($customer->name ?? 'CONSUMIDOR FINAL', 30);
        $line .= $this->formatAmount($doc->total ?? 0);
        $line .= $this->formatAmount(0);                               // no gravados
        $line .= $this->formatAmount(0);                               // percepciones no categ
        $line .= $this->formatAmount(0);                               // exentas
        $line .= $this->formatAmount($doc->percepciones ?? 0);        // percepciones nacionales
        $line .= $this->formatAmount(0);                               // percepciones IIBB
        $line .= $this->formatAmount(0);                               // percepciones municipales
        $line .= $this->formatAmount(0);                               // impuestos internos
        $line .= self::MONEDA_PESOS;
        $line .= self::TIPO_CAMBIO_DEFAULT;
        $line .= $this->formatNumeric($cantAlicuotas, 1);
        $line .= ' ';                                                   // código operación
        $line .= $this->formatAmount($doc->otros_impuestos ?? 0);     // otros tributos
        $line .= $this->formatDate($doc->due_date ?? $doc->issue_date);

        return $line;
    }

    // ─── VENTAS ALÍCUOTAS (62 chars) ─────────────────────────────

    public function generateVentasAlicuotas(int $companyId, int $year, int $month): string
    {
        $lines = [];

        $invoices = SalesInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->with('pointOfSale')
            ->get();

        foreach ($invoices as $inv) {
            $this->appendVentaAlicuotaLines($lines, $inv, false);
        }

        $creditNotes = CreditNote::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('cae')
            ->with('pointOfSale')
            ->get();

        foreach ($creditNotes as $cn) {
            $this->appendVentaAlicuotaLines($lines, $cn, true);
        }

        $this->validateLineLength($lines, 62, 'ventas_alicuotas');

        return implode("\r\n", $lines);
    }

    private function appendVentaAlicuotaLines(array &$lines, $doc, bool $isCreditNote): void
    {
        $tipoCbte = $this->getVentaVoucherTypeCode($doc->voucher_type, $isCreditNote);
        $pv = $this->formatNumeric(
            $doc->pointOfSale ? $doc->pointOfSale->code : ($doc->point_of_sale ?? '0'),
            5
        );
        $nroCbte = $this->formatNumeric(
            $doc->afip_voucher_number ?? $doc->invoice_number ?? $doc->credit_note_number ?? '0',
            20
        );

        foreach ($this->getAlicuotasFromDoc($doc) as $alicuota) {
            $line = '';
            $line .= $this->formatNumeric($tipoCbte, 3);
            $line .= $pv;
            $line .= $nroCbte;
            $line .= $this->formatAmount($alicuota['neto']);
            $line .= $alicuota['codigo'];
            $line .= $this->formatAmount($alicuota['iva']);
            $lines[] = $line;
        }
    }

    // ─── COMPRAS CABECERA (325 chars) ────────────────────────────

    public function generateComprasCabecera(int $companyId, int $year, int $month): string
    {
        $lines = [];

        $invoices = PurchaseInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->with('supplier')
            ->get();

        foreach ($invoices as $inv) {
            $lines[] = $this->buildCompraCabeceraLine($inv);
        }

        $this->validateLineLength($lines, 325, 'compras_cbte');

        return implode("\r\n", $lines);
    }

    private function buildCompraCabeceraLine(PurchaseInvoice $inv): string
    {
        $supplier = $inv->supplier;
        $cuit = $this->normalizeCuit($inv->cuit_emisor ?? $supplier->tax_id ?? '');
        $tipoCbte = $this->getCompraVoucherTypeCode($inv->voucher_type);
        $cantAlicuotas = $this->countAlicuotas($inv);
        $creditoFiscal = ($inv->iva_21 ?? 0) + ($inv->iva_10_5 ?? 0) + ($inv->iva_27 ?? 0);

        $line = '';
        $line .= $this->formatDate($inv->issue_date);                                          // 1: Fecha (8)
        $line .= $this->formatNumeric($tipoCbte, 3);                                           // 2: Tipo cbte (3)
        $line .= $this->formatNumeric($inv->point_of_sale ?? '0', 5);                         // 3: PV (5)
        $line .= $this->formatNumeric($inv->invoice_number ?? '0', 20);                       // 4: Nro cbte (20)
        $line .= $this->formatText('', 16);                                                     // 5: Despacho importación (16)
        $line .= $this->formatNumeric(self::DOC_CUIT, 2);                                     // 6: Cod doc vendedor (2)
        $line .= $this->formatNumeric($cuit, 20);                                              // 7: Nro id vendedor (20)
        $line .= $this->formatText($supplier->business_name ?? $supplier->name ?? '', 30);    // 8: Nombre vendedor (30)
        $line .= $this->formatAmount($inv->total ?? 0);                                       // 9: Importe total (15)
        $line .= $this->formatAmount(0);                                                        // 10: No gravados (15)
        $line .= $this->formatAmount(0);                                                        // 11: Exentas (15)
        $line .= $this->formatAmount($inv->percepciones ?? 0);                                // 12: Percepciones IVA (15)
        $line .= $this->formatAmount(0);                                                        // 13: Percepciones nacionales (15)
        $line .= $this->formatAmount(0);                                                        // 14: Percepciones IIBB (15)
        $line .= $this->formatAmount(0);                                                        // 15: Percepciones municipales (15)
        $line .= $this->formatAmount(0);                                                        // 16: Impuestos internos (15)
        $line .= self::MONEDA_PESOS;                                                            // 17: Moneda (3)
        $line .= self::TIPO_CAMBIO_DEFAULT;                                                     // 18: Tipo cambio (10)
        $line .= $this->formatNumeric($cantAlicuotas, 1);                                     // 19: Cant alícuotas (1)
        $line .= ' ';                                                                           // 20: Código operación (1)
        $line .= $this->formatAmount($creditoFiscal);                                          // 21: Crédito fiscal computable (15)
        $line .= $this->formatAmount($inv->otros_impuestos ?? 0);                             // 22: Otros tributos (15)
        $line .= $this->formatNumeric('', 11);                                                 // 23: CUIT emisor/corredor (11)
        $line .= $this->formatText('', 30);                                                     // 24: Denominación emisor/corredor (30)
        $line .= $this->formatAmount(0);                                                        // 25: IVA comisión (15)

        return $line;
    }

    // ─── COMPRAS ALÍCUOTAS (84 chars) ────────────────────────────

    public function generateComprasAlicuotas(int $companyId, int $year, int $month): string
    {
        $lines = [];

        $invoices = PurchaseInvoice::where('company_id', $companyId)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->with('supplier')
            ->get();

        foreach ($invoices as $inv) {
            $tipoCbte = $this->getCompraVoucherTypeCode($inv->voucher_type);
            $pv = $this->formatNumeric($inv->point_of_sale ?? '0', 5);
            $nroCbte = $this->formatNumeric($inv->invoice_number ?? '0', 20);
            $cuit = $this->normalizeCuit($inv->cuit_emisor ?? $inv->supplier->tax_id ?? '');

            foreach ($this->getAlicuotasFromDoc($inv) as $alicuota) {
                $line = '';
                $line .= $this->formatNumeric($tipoCbte, 3);
                $line .= $pv;
                $line .= $nroCbte;
                $line .= $this->formatNumeric(self::DOC_CUIT, 2);
                $line .= $this->formatNumeric($cuit, 20);
                $line .= $this->formatAmount($alicuota['neto']);
                $line .= $alicuota['codigo'];
                $line .= $this->formatAmount($alicuota['iva']);
                $lines[] = $line;
            }
        }

        $this->validateLineLength($lines, 84, 'compras_alicuotas');

        return implode("\r\n", $lines);
    }

    // ─── HELPERS ─────────────────────────────────────────────────

    private function formatAmount(float $amount, int $length = 15): string
    {
        $cents = (int) round(abs($amount) * 100);

        return str_pad((string) $cents, $length, '0', STR_PAD_LEFT);
    }

    private function formatText(string $text, int $length): string
    {
        return str_pad(mb_substr($text, 0, $length), $length, ' ', STR_PAD_RIGHT);
    }

    private function formatNumeric(string $value, int $length): string
    {
        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }

    private function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('Ymd') : '00000000';
    }

    private function getDocType($customer): string
    {
        if (! $customer) {
            return self::DOC_SIN_IDENTIFICAR;
        }

        $tax = strtolower($customer->tax ?? '');
        if (in_array($tax, ['responsable inscripto', 'ri', 'monotributista', 'monotributo', 'exento', 'iva exento'])) {
            return self::DOC_CUIT;
        }

        return self::DOC_SIN_IDENTIFICAR;
    }

    private function getDocNumber($customer): string
    {
        if (! $customer || ! $customer->taxId) {
            return '0';
        }

        return $this->normalizeCuit($customer->taxId);
    }

    private function normalizeCuit(string $cuit): string
    {
        return preg_replace('/[^0-9]/', '', $cuit);
    }

    private function getVentaVoucherTypeCode(string $voucherType, bool $isCreditNote): string
    {
        if ($isCreditNote) {
            $letter = trim(str_replace(['Nota de Crédito', 'Nota de Credito', 'NC'], '', $voucherType));
            if (! $letter) {
                $letter = substr($voucherType, -1);
            }

            return match (strtoupper(trim($letter))) {
                'A' => '003',
                'B' => '008',
                'C' => '013',
                default => '008',
            };
        }

        return self::VOUCHER_TYPES_VENTAS[$voucherType]
            ?? self::VOUCHER_TYPES_VENTAS['Factura '.$voucherType]
            ?? '006';
    }

    private function getCompraVoucherTypeCode(string $voucherType): string
    {
        $type = strtoupper(trim($voucherType));

        return match (true) {
            str_contains($type, 'CREDITO') || str_contains($type, 'NC') => match (true) {
                str_contains($type, 'A') => '003',
                str_contains($type, 'C') => '013',
                default => '008',
            },
            str_contains($type, 'A') || $type === 'A' => '001',
            str_contains($type, 'C') || $type === 'C' => '011',
            default => '006',
        };
    }

    private function countAlicuotas($doc): int
    {
        $count = 0;
        if (($doc->iva_21 ?? 0) > 0) {
            $count++;
        }
        if (($doc->iva_10_5 ?? 0) > 0) {
            $count++;
        }
        if (($doc->iva_27 ?? 0) > 0) {
            $count++;
        }

        return max($count, 1);
    }

    private function getAlicuotasFromDoc($doc): array
    {
        $alicuotas = [];
        $iva21 = (float) ($doc->iva_21 ?? 0);
        $iva105 = (float) ($doc->iva_10_5 ?? 0);
        $iva27 = (float) ($doc->iva_27 ?? 0);
        $activeRates = ($iva21 > 0 ? 1 : 0) + ($iva105 > 0 ? 1 : 0) + ($iva27 > 0 ? 1 : 0);

        if ($iva21 > 0) {
            $neto = ($activeRates === 1 && isset($doc->subtotal))
                ? (float) $doc->subtotal
                : round($iva21 / 0.21, 2);
            $alicuotas[] = ['neto' => $neto, 'iva' => $iva21, 'codigo' => self::ALICUOTAS['21']];
        }
        if ($iva105 > 0) {
            $neto = ($activeRates === 1 && isset($doc->subtotal))
                ? (float) $doc->subtotal
                : round($iva105 / 0.105, 2);
            $alicuotas[] = ['neto' => $neto, 'iva' => $iva105, 'codigo' => self::ALICUOTAS['10.5']];
        }
        if ($iva27 > 0) {
            $neto = ($activeRates === 1 && isset($doc->subtotal))
                ? (float) $doc->subtotal
                : round($iva27 / 0.27, 2);
            $alicuotas[] = ['neto' => $neto, 'iva' => $iva27, 'codigo' => self::ALICUOTAS['27']];
        }

        if (empty($alicuotas)) {
            $alicuotas[] = [
                'neto' => (float) ($doc->subtotal ?? $doc->total ?? 0),
                'iva' => 0,
                'codigo' => self::ALICUOTAS['0'],
            ];
        }

        return $alicuotas;
    }

    private function validateLineLength(array $lines, int $expected, string $context): void
    {
        foreach ($lines as $i => $line) {
            $len = strlen($line);
            if ($len !== $expected) {
                Log::error("Libro IVA: línea {$i} de {$context} tiene {$len} chars (esperado {$expected})");
            }
        }
    }
}
