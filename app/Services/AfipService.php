<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\SalesInvoice;
use Exception;
use Illuminate\Support\Facades\Log;
use SoapClient;

class AfipSoapClient extends SoapClient
{
    private ?int $condicionIvaReceptorId = null;

    public function setCondicionIvaReceptorId(?int $value): void
    {
        $this->condicionIvaReceptorId = $value;
    }

    #[\Override]
    public function __doRequest($request, $location, $action, $version, $oneWay = false): ?string
    {
        if ($this->condicionIvaReceptorId !== null && strpos($request, 'FECAESolicitar') !== false) {
            $pattern = '/<([^>]*?)DocNro>(\d+)<\/([^>]*?)DocNro>/';
            if (preg_match($pattern, $request, $m)) {
                $prefix = $m[1];
                $closePrefix = $m[3];
                $tag = '<' . $prefix . 'CondicionIVAReceptorId>' . $this->condicionIvaReceptorId .
                       '</' . $closePrefix . 'CondicionIVAReceptorId>';
                $request = preg_replace($pattern, $m[0] . $tag, $request, 1);
            }
        }

        return parent::__doRequest($request, $location, $action, $version, $oneWay);
    }
}

class AfipService
{
    protected string $cuit;
    protected string $certPath;
    protected string $keyPath;
    protected bool $production;

    protected const WSAA_WSDL_HOMO = 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL';
    protected const WSAA_WSDL_PROD = 'https://wsaa.afip.gov.ar/ws/services/LoginCms?WSDL';

    protected const WSFE_WSDL_HOMO = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL';
    protected const WSFE_WSDL_PROD = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL';

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
        $this->cuit = config('afip.cuit');
        $this->certPath = base_path(config('afip.cert_path'));
        $this->keyPath = base_path(config('afip.key_path'));
        $this->production = (bool) config('afip.production');
    }

    // ─── WSAA ────────────────────────────────────────────────

    protected function getTokenAuthorization(): object
    {
        $taFile = storage_path('app/afip/ta_wsfe_' . ($this->production ? 'prod' : 'homo') . '.json');

        if (file_exists($taFile)) {
            $data = json_decode(file_get_contents($taFile));
            if ($data && isset($data->token, $data->sign, $data->expiration)) {
                $expiration = new \DateTime($data->expiration);
                if ($expiration > new \DateTime('now', new \DateTimeZone('America/Buenos_Aires'))) {
                    return $data;
                }
            }
        }

        $ta = $this->authenticate('wsfe');

        $ta->expiration = (new \DateTime('now', new \DateTimeZone('America/Buenos_Aires')))
            ->modify('+11 hours')
            ->format('Y-m-d\TH:i:s');

        file_put_contents($taFile, json_encode($ta));

        return $ta;
    }

    protected function authenticate(string $service): object
    {
        $tra = $this->createTRA($service);
        $cms = $this->signTRA($tra);

        $wsaaWsdl = $this->production ? self::WSAA_WSDL_PROD : self::WSAA_WSDL_HOMO;

        $client = new SoapClient($wsaaWsdl, [
            'soap_version' => SOAP_1_1,
            'trace' => true,
            'exceptions' => true,
            'stream_context' => stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]),
        ]);

        try {
            $response = $client->loginCms(['in0' => $cms]);
        } catch (\SoapFault $e) {
            if (str_contains($e->getMessage(), 'ya posee un TA')) {
                Log::warning('AFIP WSAA: TA still valid, waiting and retrying', ['service' => $service]);
                sleep(5);

                $tra = $this->createTRA($service);
                $cms = $this->signTRA($tra);

                try {
                    $response = $client->loginCms(['in0' => $cms]);
                } catch (\SoapFault $retryEx) {
                    if (str_contains($retryEx->getMessage(), 'ya posee un TA')) {
                        throw new Exception(
                            'AFIP WSAA reporta un TA vigente que no tenemos en cache. ' .
                            'Esto ocurre si el token fue emitido en otra sesión. ' .
                            'Espere unos minutos e intente nuevamente.'
                        );
                    }
                    throw $retryEx;
                }
            } else {
                throw $e;
            }
        }

        $loginReturn = $response->loginCmsReturn;

        $xml = new \SimpleXMLElement($loginReturn);
        $token = (string) $xml->credentials->token;
        $sign = (string) $xml->credentials->sign;

        Log::info('AFIP WSAA authentication successful', ['service' => $service]);

        return (object) ['token' => $token, 'sign' => $sign];
    }

    protected function createTRA(string $service): string
    {
        $now = new \DateTime('now', new \DateTimeZone('America/Buenos_Aires'));
        $from = clone $now;
        $from->modify('-2 minutes');
        $to = clone $now;
        $to->modify('+12 hours');

        $uniqueId = $now->getTimestamp();
        $generationTime = $from->format('Y-m-d\TH:i:s');
        $expirationTime = $to->format('Y-m-d\TH:i:s');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
    <header>
        <uniqueId>{$uniqueId}</uniqueId>
        <generationTime>{$generationTime}</generationTime>
        <expirationTime>{$expirationTime}</expirationTime>
    </header>
    <service>{$service}</service>
</loginTicketRequest>
XML;
    }

    protected function signTRA(string $tra): string
    {
        $tempTra = tempnam(sys_get_temp_dir(), 'afip_tra_');
        $tempCms = tempnam(sys_get_temp_dir(), 'afip_cms_');

        file_put_contents($tempTra, $tra);

        $certContent = file_get_contents($this->certPath);
        $keyContent = file_get_contents($this->keyPath);

        if (!$certContent || !$keyContent) {
            throw new Exception('No se pudieron leer los certificados AFIP');
        }

        $signed = openssl_pkcs7_sign(
            $tempTra,
            $tempCms,
            $certContent,
            $keyContent,
            [],
            PKCS7_BINARY | PKCS7_NOSIGS
        );

        if (!$signed) {
            @unlink($tempTra);
            @unlink($tempCms);
            throw new Exception('Error al firmar el TRA: ' . openssl_error_string());
        }

        $cmsContent = file_get_contents($tempCms);
        @unlink($tempTra);
        @unlink($tempCms);

        $parts = explode("\n\n", $cmsContent);
        if (count($parts) < 2) {
            $parts = explode("\r\n\r\n", $cmsContent);
        }

        return trim($parts[1] ?? $cmsContent);
    }

    // ─── WSFEv1 ──────────────────────────────────────────────

    protected function getWsfeClient(): AfipSoapClient
    {
        $wsdl = $this->production ? self::WSFE_WSDL_PROD : self::WSFE_WSDL_HOMO;

        return new AfipSoapClient($wsdl, [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]),
        ]);
    }

    protected function getAuthParams(): array
    {
        $ta = $this->getTokenAuthorization();

        return [
            'Token' => $ta->token,
            'Sign' => $ta->sign,
            'Cuit' => $this->cuit,
        ];
    }

    public function getServerStatus(): array
    {
        try {
            $client = $this->getWsfeClient();
            $result = $client->FEDummy();

            return [
                'success' => true,
                'AppServer' => $result->FEDummyResult->AppServer ?? 'N/A',
                'DbServer' => $result->FEDummyResult->DbServer ?? 'N/A',
                'AuthServer' => $result->FEDummyResult->AuthServer ?? 'N/A',
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
        $client = $this->getWsfeClient();
        $result = $client->FECompUltimoAutorizado([
            'Auth' => $this->getAuthParams(),
            'PtoVta' => $pointOfSale,
            'CbteTipo' => $voucherType,
        ]);

        return $result->FECompUltimoAutorizadoResult->CbteNro;
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

    public static function getCondicionIvaReceptor(string $taxCondition, string $voucherType = 'B'): int
    {
        if ($voucherType === 'A') {
            return match (strtolower($taxCondition)) {
                'responsable inscripto', 'ri' => 1,
                'monotributista', 'monotributo' => 6,
                default => 1,
            };
        }

        return match (strtolower($taxCondition)) {
            'exento', 'iva exento' => 4,
            'consumidor final', 'cf' => 5,
            'monotributista', 'monotributo' => 6,
            default => 5,
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
        $condIvaReceptor = self::getCondicionIvaReceptor($customer->tax ?? 'consumidor final', $invoice->voucher_type);

        $lastVoucher = $this->getLastVoucher($afipPosNumber, $voucherTypeId);
        $nextNumber = $lastVoucher + 1;

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
        $totalIva = array_sum(array_column($ivaArray, 'Importe'));
        $impTotal = round($netAmount + $exemptAmount + $totalIva + $totalTrib, 2);

        $feCaeReq = [
            'FeCabReq' => [
                'CantReg' => 1,
                'PtoVta' => $afipPosNumber,
                'CbteTipo' => $voucherTypeId,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => [
                    'Concepto' => 3,
                    'DocTipo' => $docTipo,
                    'DocNro' => $docNro,
                    'CondicionIvaReceptor' => $condIvaReceptor,
                    'CbteDesde' => $nextNumber,
                    'CbteHasta' => $nextNumber,
                    'CbteFch' => $invoice->issue_date->format('Ymd'),
                    'ImpTotal' => $impTotal,
                    'ImpTotConc' => 0,
                    'ImpNeto' => round($netAmount, 2),
                    'ImpOpEx' => round($exemptAmount, 2),
                    'ImpIVA' => round($totalIva, 2),
                    'ImpTrib' => round($totalTrib, 2),
                    'FchServDesde' => $invoice->issue_date->format('Ymd'),
                    'FchServHasta' => $invoice->issue_date->format('Ymd'),
                    'FchVtoPago' => ($invoice->due_date ?? $invoice->issue_date)->format('Ymd'),
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                ],
            ],
        ];

        if (! empty($ivaArray)) {
            $feCaeReq['FeDetReq']['FECAEDetRequest']['Iva'] = ['AlicIva' => $ivaArray];
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
            $feCaeReq['FeDetReq']['FECAEDetRequest']['Tributos'] = ['Tributo' => $tributos];
        }

        try {
            $client = $this->getWsfeClient();
            $client->setCondicionIvaReceptorId($condIvaReceptor);
            $result = $client->FECAESolicitar([
                'Auth' => $this->getAuthParams(),
                'FeCAEReq' => $feCaeReq,
            ]);

            $feCaeResult = $result->FECAESolicitarResult;
            $detResponse = $feCaeResult->FeDetResp->FECAEDetResponse;

            $response = [
                'cae' => $detResponse->CAE ?? null,
                'cae_expiration' => isset($detResponse->CAEFchVto) ? $this->formatAfipDate($detResponse->CAEFchVto) : null,
                'voucher_number' => $nextNumber,
                'result' => $detResponse->Resultado ?? null,
                'observations' => isset($detResponse->Observaciones)
                    ? json_decode(json_encode($detResponse->Observaciones), true)
                    : null,
                'full_response' => json_decode(json_encode($feCaeResult), true),
            ];

            Log::info('AFIP voucher created', [
                'invoice_id' => $invoice->id,
                'cae' => $response['cae'],
                'voucher_number' => $nextNumber,
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

    public function createCreditNote(CreditNote $creditNote): array
    {
        $pointOfSale = $creditNote->pointOfSale;
        $customer = $creditNote->customer;
        $invoice = $creditNote->salesInvoice;

        $voucherTypeId = self::getVoucherTypeId($creditNote->voucher_type, 'nota_credito');
        $afipPosNumber = $pointOfSale->afip_pos_number;

        $docTipo = self::getDocTipo($customer->tax ?? 'consumidor final');
        $docNro = $docTipo === 99 ? 0 : intval(str_replace('-', '', $customer->taxId ?? '0'));
        $condIvaReceptor = self::getCondicionIvaReceptor($customer->tax ?? 'consumidor final', $creditNote->voucher_type);

        $lastVoucher = $this->getLastVoucher($afipPosNumber, $voucherTypeId);
        $nextNumber = $lastVoucher + 1;

        $ivaArray = [];
        $netAmount = 0;
        $exemptAmount = 0;

        $itemsByIva = $creditNote->items->groupBy('iva_rate');

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

        $totalTrib = floatval($creditNote->percepciones) + floatval($creditNote->otros_impuestos);
        $totalIva = array_sum(array_column($ivaArray, 'Importe'));
        $impTotal = round($netAmount + $exemptAmount + $totalIva + $totalTrib, 2);

        $invoiceTypeId = self::getVoucherTypeId($invoice->voucher_type, 'factura');
        $invoicePosNumber = $invoice->pointOfSale ? $invoice->pointOfSale->afip_pos_number : $afipPosNumber;
        $invoiceNumber = $invoice->afip_voucher_number ?? intval($invoice->invoice_number);

        $feCaeReq = [
            'FeCabReq' => [
                'CantReg' => 1,
                'PtoVta' => $afipPosNumber,
                'CbteTipo' => $voucherTypeId,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => [
                    'Concepto' => 3,
                    'DocTipo' => $docTipo,
                    'DocNro' => $docNro,
                    'CondicionIvaReceptor' => $condIvaReceptor,
                    'CbteDesde' => $nextNumber,
                    'CbteHasta' => $nextNumber,
                    'CbteFch' => $creditNote->issue_date->format('Ymd'),
                    'ImpTotal' => $impTotal,
                    'ImpTotConc' => 0,
                    'ImpNeto' => round($netAmount, 2),
                    'ImpOpEx' => round($exemptAmount, 2),
                    'ImpIVA' => round($totalIva, 2),
                    'ImpTrib' => round($totalTrib, 2),
                    'FchServDesde' => $creditNote->issue_date->format('Ymd'),
                    'FchServHasta' => $creditNote->issue_date->format('Ymd'),
                    'FchVtoPago' => $creditNote->issue_date->format('Ymd'),
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                    'CbtesAsoc' => [
                        'CbteAsoc' => [
                            'Tipo' => $invoiceTypeId,
                            'PtoVta' => $invoicePosNumber,
                            'Nro' => $invoiceNumber,
                            'Cuit' => $this->cuit,
                            'CbteFch' => $invoice->issue_date->format('Ymd'),
                        ],
                    ],
                ],
            ],
        ];

        if (! empty($ivaArray)) {
            $feCaeReq['FeDetReq']['FECAEDetRequest']['Iva'] = ['AlicIva' => $ivaArray];
        }

        if ($totalTrib > 0) {
            $tributos = [];
            if (floatval($creditNote->percepciones) > 0) {
                $tributos[] = [
                    'Id' => 99,
                    'Desc' => 'Percepciones',
                    'BaseImp' => round($netAmount, 2),
                    'Alic' => 0,
                    'Importe' => round(floatval($creditNote->percepciones), 2),
                ];
            }
            if (floatval($creditNote->otros_impuestos) > 0) {
                $tributos[] = [
                    'Id' => 99,
                    'Desc' => 'Otros impuestos',
                    'BaseImp' => round($netAmount, 2),
                    'Alic' => 0,
                    'Importe' => round(floatval($creditNote->otros_impuestos), 2),
                ];
            }
            $feCaeReq['FeDetReq']['FECAEDetRequest']['Tributos'] = ['Tributo' => $tributos];
        }

        try {
            $client = $this->getWsfeClient();
            $client->setCondicionIvaReceptorId($condIvaReceptor);
            $result = $client->FECAESolicitar([
                'Auth' => $this->getAuthParams(),
                'FeCAEReq' => $feCaeReq,
            ]);

            $feCaeResult = $result->FECAESolicitarResult;
            $detResponse = $feCaeResult->FeDetResp->FECAEDetResponse;

            $response = [
                'cae' => $detResponse->CAE ?? null,
                'cae_expiration' => isset($detResponse->CAEFchVto) ? $this->formatAfipDate($detResponse->CAEFchVto) : null,
                'voucher_number' => $nextNumber,
                'result' => $detResponse->Resultado ?? null,
                'observations' => isset($detResponse->Observaciones)
                    ? json_decode(json_encode($detResponse->Observaciones), true)
                    : null,
                'full_response' => json_decode(json_encode($feCaeResult), true),
            ];

            Log::info('AFIP credit note created', [
                'credit_note_id' => $creditNote->id,
                'cae' => $response['cae'],
                'voucher_number' => $nextNumber,
                'result' => $response['result'],
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('AFIP credit note creation failed', [
                'credit_note_id' => $creditNote->id,
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

    public function invalidateTokenCache(): void
    {
        $taFile = storage_path('app/afip/ta_wsfe_' . ($this->production ? 'prod' : 'homo') . '.json');
        if (file_exists($taFile)) {
            @unlink($taFile);
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
