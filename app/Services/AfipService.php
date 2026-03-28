<?php

namespace App\Services;

use App\Models\Company;
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
                $tag = '<'.$prefix.'CondicionIVAReceptorId>'.$this->condicionIvaReceptorId.
                       '</'.$closePrefix.'CondicionIVAReceptorId>';
                $request = preg_replace($pattern, $m[0].$tag, $request, 1);
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

    protected const PADRON_WSDL_HOMO = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL';

    protected const PADRON_WSDL_PROD = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL';

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

    public function __construct(?Company $company = null)
    {
        $company = $company ?? active_company();

        $this->cuit = $company?->cuit
            ? str_replace('-', '', $company->cuit)
            : config('afip.cuit');
        $this->certPath = $company?->afip_cert_path
            ? base_path($company->afip_cert_path)
            : base_path(config('afip.cert_path'));
        $this->keyPath = $company?->afip_key_path
            ? base_path($company->afip_key_path)
            : base_path(config('afip.key_path'));
        $this->production = $company?->afip_production ?? (bool) config('afip.production');
    }

    // ─── WSAA ────────────────────────────────────────────────

    protected function getTokenAuthorization(string $service = 'wsfe'): object
    {
        $taFile = storage_path('app/afip/ta_'.$service.'_'.$this->cuit.'_'.($this->production ? 'prod' : 'homo').'.json');

        if (file_exists($taFile)) {
            $data = json_decode(file_get_contents($taFile));
            if ($data && isset($data->token, $data->sign, $data->expiration)) {
                $expiration = new \DateTime($data->expiration);
                if ($expiration > new \DateTime('now', new \DateTimeZone('America/Buenos_Aires'))) {
                    return $data;
                }
            }
        }

        $ta = $this->authenticate($service);

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
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'ciphers' => 'DEFAULT@SECLEVEL=0',
                ],
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
                            'AFIP WSAA reporta un TA vigente que no tenemos en cache. '.
                            'Esto ocurre si el token fue emitido en otra sesión. '.
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

        if (! $certContent || ! $keyContent) {
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

        if (! $signed) {
            @unlink($tempTra);
            @unlink($tempCms);
            throw new Exception('Error al firmar el TRA: '.openssl_error_string());
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
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'ciphers' => 'DEFAULT@SECLEVEL=0',
                ],
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

    protected static array $provincias = [
        0 => 'CABA',
        1 => 'Buenos Aires',
        2 => 'Catamarca',
        3 => 'Córdoba',
        4 => 'Corrientes',
        5 => 'Entre Ríos',
        6 => 'Jujuy',
        7 => 'Mendoza',
        8 => 'La Rioja',
        9 => 'Salta',
        10 => 'San Juan',
        11 => 'San Luis',
        12 => 'Santa Fe',
        13 => 'Santiago del Estero',
        14 => 'Tucumán',
        16 => 'Chaco',
        17 => 'Chubut',
        18 => 'Formosa',
        19 => 'Misiones',
        20 => 'Neuquén',
        21 => 'La Pampa',
        22 => 'Río Negro',
        23 => 'Santa Cruz',
        24 => 'Tierra del Fuego',
    ];

    // ─── Padrón AFIP (ws_sr_padron_a5) ─────────────────────

    public function consultarPadron(string $cuit): array
    {
        $cuit = preg_replace('/\D/', '', $cuit);

        if (strlen($cuit) !== 11) {
            return ['success' => false, 'error' => 'El CUIT debe tener 11 dígitos.'];
        }

        try {
            $ta = $this->getTokenAuthorization('ws_sr_padron_a5');

            $wsdl = $this->production ? self::PADRON_WSDL_PROD : self::PADRON_WSDL_HOMO;

            $client = new SoapClient($wsdl, [
                'soap_version' => SOAP_1_1,
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 15,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'ciphers' => 'DEFAULT@SECLEVEL=0',
                    ],
                    'http' => [
                        'timeout' => 30,
                    ],
                ]),
            ]);

            $result = $client->getPersona([
                'token' => $ta->token,
                'sign' => $ta->sign,
                'cuitRepresentada' => $this->cuit,
                'idPersona' => $cuit,
            ]);

            $persona = $result->personaReturn->datosGenerales ?? null;

            if (! $persona) {
                return ['success' => false, 'error' => 'CUIT no encontrado en el padrón de AFIP.'];
            }

            $razonSocial = $persona->razonSocial
                ?? trim(($persona->apellido ?? '').' '.($persona->nombre ?? ''));

            $estadoCuit = $persona->estadoClave ?? 'DESCONOCIDO';

            $domicilio = $this->parseDomicilioFiscal($result->personaReturn);
            $condicionIva = $this->parseCondicionIva($result->personaReturn);
            $actividad = $this->parseActividadPrincipal($result->personaReturn);

            Log::info('AFIP padrón consulta exitosa', [
                'cuit' => $cuit,
                'razon_social' => $razonSocial,
                'estado' => $estadoCuit,
            ]);

            return [
                'success' => true,
                'razon_social' => $razonSocial,
                'condicion_iva' => $condicionIva['label'],
                'condicion_iva_id' => $condicionIva['id'],
                'domicilio' => $domicilio,
                'actividad_principal' => $actividad,
                'estado_cuit' => $estadoCuit,
            ];
        } catch (\SoapFault $e) {
            Log::error('AFIP padrón SOAP error', ['cuit' => $cuit, 'error' => $e->getMessage()]);

            if (str_contains($e->getMessage(), 'No existe persona')) {
                return ['success' => false, 'error' => 'CUIT no encontrado en el padrón de AFIP.'];
            }

            return ['success' => false, 'error' => 'Error al consultar AFIP: '.$e->getMessage()];
        } catch (Exception $e) {
            Log::error('AFIP padrón error', ['cuit' => $cuit, 'error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'No se pudo conectar con AFIP. Intente nuevamente.'];
        }
    }

    protected function parseDomicilioFiscal(object $personaReturn): array
    {
        $domicilioFiscal = null;

        $domicilios = $personaReturn->datosGenerales->domicilioFiscal ?? null;
        if ($domicilios) {
            $domicilioFiscal = $domicilios;
        }

        if (! $domicilioFiscal) {
            return [
                'direccion' => '',
                'localidad' => '',
                'provincia' => '',
                'cod_postal' => '',
            ];
        }

        $provinciaCode = (int) ($domicilioFiscal->idProvincia ?? -1);
        $provincia = self::$provincias[$provinciaCode] ?? '';

        return [
            'direccion' => trim(($domicilioFiscal->direccion ?? '').' '.($domicilioFiscal->numero ?? '')),
            'localidad' => $domicilioFiscal->localidad ?? $domicilioFiscal->descripcionProvincia ?? '',
            'provincia' => $provincia,
            'cod_postal' => $domicilioFiscal->codPostal ?? '',
        ];
    }

    protected function parseCondicionIva(object $personaReturn): array
    {
        $impuestos = $personaReturn->datosRegimenGeneral->impuesto ?? [];
        if (! is_array($impuestos)) {
            $impuestos = [$impuestos];
        }

        $tieneIva = false;
        $tieneMonotributo = false;

        foreach ($impuestos as $impuesto) {
            $idImpuesto = (int) ($impuesto->idImpuesto ?? 0);
            $estado = strtoupper($impuesto->estado ?? '');

            if ($idImpuesto === 30 && $estado === 'AC') {
                $tieneIva = true;
            }
            if ($idImpuesto === 20 && $estado === 'AC') {
                $tieneMonotributo = true;
            }
        }

        $categorias = $personaReturn->datosMonotributo->categoriaMonotributo ?? null;
        if ($categorias && ! $tieneMonotributo) {
            $tieneMonotributo = true;
        }

        if ($tieneIva) {
            return ['label' => 'Responsable Inscripto', 'id' => 1];
        }

        if ($tieneMonotributo) {
            return ['label' => 'Monotributista', 'id' => 6];
        }

        $tipoPersona = $personaReturn->datosGenerales->tipoPersona ?? '';
        if ($tipoPersona === 'JURIDICA') {
            return ['label' => 'Responsable Inscripto', 'id' => 1];
        }

        return ['label' => 'Consumidor Final', 'id' => 5];
    }

    protected function parseActividadPrincipal(object $personaReturn): string
    {
        $actividades = $personaReturn->datosRegimenGeneral->actividad ?? [];
        if (! is_array($actividades)) {
            $actividades = [$actividades];
        }

        foreach ($actividades as $actividad) {
            $orden = (int) ($actividad->orden ?? 999);
            if ($orden === 1) {
                return $actividad->descripcionActividad ?? '';
            }
        }

        if (! empty($actividades)) {
            return $actividades[0]->descripcionActividad ?? '';
        }

        $actividadesMono = $personaReturn->datosMonotributo->actividadMonotributista ?? [];
        if (! is_array($actividadesMono)) {
            $actividadesMono = [$actividadesMono];
        }

        if (! empty($actividadesMono)) {
            return $actividadesMono[0]->descripcionActividad ?? '';
        }

        return '';
    }

    public function invalidateTokenCache(string $service = 'wsfe'): void
    {
        $taFile = storage_path('app/afip/ta_'.$service.'_'.$this->cuit.'_'.($this->production ? 'prod' : 'homo').'.json');
        if (file_exists($taFile)) {
            @unlink($taFile);
        }
    }

    protected function formatAfipDate(string $date): string
    {
        if (strlen($date) === 8) {
            return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
        }

        return $date;
    }
}
