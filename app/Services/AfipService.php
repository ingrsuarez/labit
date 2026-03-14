<?php

namespace App\Services;

use App\Models\SalesInvoice;
use Exception;
use Illuminate\Support\Facades\Log;

require_once base_path('vendor/afipsdk/afip.php/src/Afip.php');

class AfipService
{
    protected \Afip $afip;

    protected static array $voucherTypes = [
        'factura' => ['A' => 1, 'B' => 6, 'C' => 11],
        'nota_credito' => ['A' => 3, 'B' => 8, 'C' => 13],
        'nota_debito' => ['A' => 2, 'B' => 7, 'C' => 12],
    ];

    protected static array $ivaIds = [
        '0' => 3,
        '10.5' => 4,
        '21' => 5,
        '27' => 6,
    ];

    public function __construct()
    {
        $this->afip = new \Afip([
            'CUIT' => config('afip.cuit'),
            'cert' => config('afip.cert_path'),
            'key' => config('afip.key_path'),
            'production' => config('afip.production'),
        ]);
    }

    public function getServerStatus(): array
    {
        try {
            $status = $this->afip->ElectronicBilling->GetServerStatus();

            return [
                'success' => true,
                'AppServer' => $status->AppServer,
                'DbServer' => $status->DbServer,
                'AuthServer' => $status->AuthServer,
            ];
        } catch (Exception $e) {
            Log::error('AFIP server status check failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getLastVoucher(int $pointOfSale, int $voucherType): int
    {
        return $this->afip->ElectronicBilling->GetLastVoucher($pointOfSale, $voucherType);
    }

    public static function getVoucherTypeId(string $letter, string $type = 'factura'): int
    {
        if (! isset(self::$voucherTypes[$type][$letter])) {
            throw new Exception("Tipo de comprobante no soportado: {$type} {$letter}");
        }

        return self::$voucherTypes[$type][$letter];
    }

    public static function getIvaId(float $rate): int
    {
        $key = rtrim(rtrim(number_format($rate, 1, '.', ''), '0'), '.');

        if (! isset(self::$ivaIds[$key])) {
            throw new Exception("Alícuota de IVA no soportada: {$rate}");
        }

        return self::$ivaIds[$key];
    }

    public static function getDocTipo(string $taxCondition): int
    {
        return match (strtolower($taxCondition)) {
            'responsable inscripto', 'ri' => 80,
            'monotributista', 'monotributo' => 80,
            'exento', 'iva exento' => 80,
            'consumidor final', 'cf' => 99,
            default => 80,
        };
    }

    public function createVoucher(SalesInvoice $invoice): array
    {
        $pointOfSale = $invoice->pointOfSale;
        $customer = $invoice->customer;

        $voucherTypeId = self::getVoucherTypeId($invoice->voucher_type);
        $afipPosNumber = $pointOfSale->afip_pos_number;

        $docTipo = self::getDocTipo($customer->tax ?? 'consumidor final');
        $docNro = $docTipo === 99 ? 0 : intval(str_replace('-', '', $customer->taxId ?? '0'));

        $ivaArray = [];
        $netAmount = 0;
        $exemptAmount = 0;

        $itemsByIva = $invoice->items->groupBy('iva_rate');

        foreach ($itemsByIva as $rate => $items) {
            $baseImp = $items->sum(fn ($item) => $item->quantity * $item->unit_price);
            $ivaImporte = $items->sum('iva_amount');

            if (floatval($rate) == 0) {
                $exemptAmount += $baseImp;
            } else {
                $netAmount += $baseImp;
                $ivaArray[] = [
                    'Id' => self::getIvaId(floatval($rate)),
                    'BaseImp' => round($baseImp, 2),
                    'Importe' => round($ivaImporte, 2),
                ];
            }
        }

        $totalTrib = floatval($invoice->percepciones) + floatval($invoice->otros_impuestos);

        $data = [
            'CantReg' => 1,
            'PtoVta' => $afipPosNumber,
            'CbteTipo' => $voucherTypeId,
            'Concepto' => 3,
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'CbteFch' => intval($invoice->issue_date->format('Ymd')),
            'ImpTotal' => round(floatval($invoice->total), 2),
            'ImpTotConc' => 0,
            'ImpNeto' => round($netAmount, 2),
            'ImpOpEx' => round($exemptAmount, 2),
            'ImpIVA' => round(floatval($invoice->iva_21) + floatval($invoice->iva_10_5) + floatval($invoice->iva_27), 2),
            'ImpTrib' => round($totalTrib, 2),
            'FchServDesde' => intval($invoice->issue_date->format('Ymd')),
            'FchServHasta' => intval($invoice->issue_date->format('Ymd')),
            'FchVtoPago' => intval(($invoice->due_date ?? $invoice->issue_date)->format('Ymd')),
            'MonId' => 'PES',
            'MonCotiz' => 1,
        ];

        if (! empty($ivaArray)) {
            $data['Iva'] = $ivaArray;
        }

        if ($totalTrib > 0) {
            $tributos = [];
            if (floatval($invoice->percepciones) > 0) {
                $tributos[] = [
                    'Id' => 99,
                    'Desc' => 'Percepciones',
                    'BaseImp' => round($netAmount, 2),
                    'Alic' => 0,
                    'Importe' => round(floatval($invoice->percepciones), 2),
                ];
            }
            if (floatval($invoice->otros_impuestos) > 0) {
                $tributos[] = [
                    'Id' => 99,
                    'Desc' => 'Otros impuestos',
                    'BaseImp' => round($netAmount, 2),
                    'Alic' => 0,
                    'Importe' => round(floatval($invoice->otros_impuestos), 2),
                ];
            }
            $data['Tributos'] = $tributos;
        }

        try {
            $result = $this->afip->ElectronicBilling->CreateNextVoucher($data, true);

            $detResponse = $result->FeDetResp->FECAEDetResponse;

            $response = [
                'cae' => $detResponse->CAE ?? null,
                'cae_expiration' => isset($detResponse->CAEFchVto) ? $this->formatAfipDate($detResponse->CAEFchVto) : null,
                'voucher_number' => $result->voucher_number ?? $detResponse->CbteDesde ?? null,
                'result' => $detResponse->Resultado ?? null,
                'observations' => isset($detResponse->Observaciones) ? json_decode(json_encode($detResponse->Observaciones), true) : null,
                'full_response' => json_decode(json_encode($result), true),
            ];

            Log::info('AFIP voucher created', [
                'invoice_id' => $invoice->id,
                'cae' => $response['cae'],
                'result' => $response['result'],
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('AFIP voucher creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'cae' => null,
                'cae_expiration' => null,
                'voucher_number' => null,
                'result' => 'R',
                'observations' => null,
                'full_response' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function formatAfipDate(string $date): string
    {
        if (strlen($date) === 8) {
            return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        }

        return $date;
    }
}
