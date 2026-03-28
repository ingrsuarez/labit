<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->full_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #333; }

        .header-table { width: 100%; border: 2px solid #000; border-collapse: collapse; }
        .header-table td { vertical-align: top; }

        .header-left, .header-right { width: 48%; padding: 10px 15px; }
        .header-center { width: 4%; text-align: center; vertical-align: top; position: relative; }

        .voucher-letter {
            display: inline-block;
            width: 40px; height: 40px;
            border: 2px solid #000;
            text-align: center;
            line-height: 40px;
            font-size: 28px;
            font-weight: bold;
            background: #fff;
            margin-top: 5px;
        }
        .voucher-code { font-size: 8px; text-align: center; margin-top: 2px; }

        .company-name { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .company-detail { font-size: 9px; color: #555; line-height: 1.5; }

        .invoice-title { font-size: 16px; font-weight: bold; text-align: right; }
        .invoice-number { font-size: 14px; font-weight: bold; text-align: right; margin-top: 4px; }
        .invoice-date { font-size: 10px; text-align: right; color: #555; margin-top: 4px; }

        .client-box { width: 100%; border: 1px solid #999; margin-top: 8px; padding: 8px 12px; }
        .client-box td { padding: 2px 0; font-size: 10px; }
        .client-label { color: #666; width: 120px; }
        .client-value { font-weight: bold; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th {
            background: #333; color: #fff; padding: 6px 8px;
            text-align: left; font-size: 9px; text-transform: uppercase;
        }
        .items-table th.right { text-align: right; }
        .items-table th.center { text-align: center; }
        .items-table td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .items-table td.right { text-align: right; }
        .items-table td.center { text-align: center; }

        .totals-table { width: 280px; margin-left: auto; margin-top: 8px; }
        .totals-table td { padding: 3px 8px; font-size: 10px; }
        .totals-label { text-align: right; color: #666; }
        .totals-value { text-align: right; font-weight: bold; }
        .totals-total td { border-top: 2px solid #333; padding-top: 6px; font-size: 13px; }

        .barcode-area { text-align: center; margin-top: 10px; }
        .barcode-number { font-family: 'Courier New', monospace; font-size: 10px; letter-spacing: 1px; margin-top: 4px; }

        .footer-line {
            margin-top: 15px; padding-top: 8px; border-top: 1px solid #ccc;
            font-size: 8px; color: #999; text-align: center;
        }

        .notes-box { margin-top: 10px; padding: 6px 10px; background: #f5f5f5; font-size: 9px; }
        .original-label { font-size: 10px; text-align: center; font-weight: bold; color: #666; margin-top: 3px; }
    </style>
</head>
<body>
    @php
        $company = $invoice->company;
        $cuit = $company ? str_replace('-', '', $company->cuit) : config('afip.cuit');
        $formattedCuit = $company ? $company->cuit : (substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1));
        $pos = $invoice->pointOfSale;
        $customer = $invoice->customer;

        $voucherTypeName = match($invoice->voucher_type) {
            'A' => 'FACTURA',
            'B' => 'FACTURA',
            'C' => 'FACTURA',
            default => 'FACTURA',
        };

        $afipCodes = ['A' => '01', 'B' => '06', 'C' => '11'];
        $afipCode = 'Cód. ' . ($afipCodes[$invoice->voucher_type] ?? '00');

        $netAmount = $invoice->items->sum(fn($i) => $i->quantity * $i->unit_price);
        $totalIva = $invoice->items->sum('iva_amount');
    @endphp

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td class="header-left" style="border-right: 1px solid #000;">
                @if(file_exists(public_path('images/logo_ipac.png')))
                    <img src="{{ public_path('images/logo_ipac.png') }}" style="max-height: 45px; margin-bottom: 6px;"><br>
                @endif
                <div class="company-name">{{ $company ? $company->name : config('afip.emisor.razon_social') }}</div>
                <div class="company-detail">
                    Domicilio: {{ $company ? $company->address . ', ' . $company->city : config('afip.emisor.domicilio') }}<br>
                    Condición frente al IVA: {{ $company ? $company->tax_condition : config('afip.emisor.condicion_iva') }}<br>
                    CUIT: {{ $formattedCuit }}<br>
                    @if($company?->iibb ?? config('afip.emisor.ingresos_brutos'))
                        Ingresos Brutos: {{ $company?->iibb ?? config('afip.emisor.ingresos_brutos') }}<br>
                    @endif
                    Inicio de Actividades: {{ $company?->activity_start?->format('d/m/Y') ?? config('afip.emisor.inicio_actividades') }}
                </div>
            </td>
            <td class="header-center" style="border-right: 1px solid #000; padding-top: 5px;">
                <div class="voucher-letter">{{ $invoice->voucher_type }}</div>
                <div class="voucher-code">{{ $afipCode }}</div>
            </td>
            <td class="header-right">
                <div class="invoice-title">{{ $voucherTypeName }}</div>
                <div class="invoice-number">
                    Punto de Venta: {{ $pos ? $pos->code : '00001' }}&nbsp;&nbsp;&nbsp;Comp. Nro: {{ $invoice->invoice_number }}
                </div>
                <div class="invoice-date">
                    Fecha de Emisión: {{ $invoice->issue_date->format('d/m/Y') }}<br>
                    @if($invoice->due_date)
                        Fecha de Vto. para el pago: {{ $invoice->due_date->format('d/m/Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="original-label">ORIGINAL</div>

    {{-- DATOS DEL RECEPTOR --}}
    <div class="client-box">
        <table width="100%">
            <tr>
                <td class="client-label">Razón Social:</td>
                <td class="client-value">{{ $customer->name }}</td>
                <td class="client-label" style="width: 100px;">CUIT/DNI:</td>
                <td class="client-value">{{ $customer->taxId ?? '-' }}</td>
            </tr>
            <tr>
                <td class="client-label">Domicilio:</td>
                <td class="client-value">{{ $customer->address ?? '-' }}{{ $customer->city ? ', ' . $customer->city : '' }}{{ $customer->state ? ', ' . $customer->state : '' }}</td>
                <td class="client-label">Cond. IVA:</td>
                <td class="client-value">{{ $customer->tax ?? '-' }}</td>
            </tr>
            @if($invoice->admission_id)
            <tr>
                <td class="client-label">Admisión:</td>
                <td class="client-value" colspan="3">#{{ $invoice->admission_id }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ITEMS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="center">#</th>
                <th>Descripción</th>
                <th class="center" style="width: 55px;">Cant.</th>
                <th class="right" style="width: 85px;">P. Unitario</th>
                @if($invoice->voucher_type === 'A')
                    <th class="center" style="width: 55px;">IVA %</th>
                    <th class="right" style="width: 75px;">IVA</th>
                @endif
                <th class="right" style="width: 90px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="center" style="color: #999;">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="center">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    @if($invoice->voucher_type === 'A')
                        <td class="center">{{ number_format($item->iva_rate, 1) }}%</td>
                        <td class="right">${{ number_format($item->iva_amount, 2, ',', '.') }}</td>
                    @endif
                    <td class="right" style="font-weight: bold;">
                        @if($invoice->voucher_type === 'A')
                            ${{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }}
                        @else
                            ${{ number_format($item->total, 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALES --}}
    <table class="totals-table">
        @if($invoice->voucher_type === 'A')
            <tr>
                <td class="totals-label">Importe Neto Gravado:</td>
                <td class="totals-value">${{ number_format($netAmount, 2, ',', '.') }}</td>
            </tr>
            @if($invoice->iva_10_5 > 0)
            <tr>
                <td class="totals-label">IVA 10,5%:</td>
                <td class="totals-value">${{ number_format($invoice->iva_10_5, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($invoice->iva_21 > 0)
            <tr>
                <td class="totals-label">IVA 21%:</td>
                <td class="totals-value">${{ number_format($invoice->iva_21, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($invoice->iva_27 > 0)
            <tr>
                <td class="totals-label">IVA 27%:</td>
                <td class="totals-value">${{ number_format($invoice->iva_27, 2, ',', '.') }}</td>
            </tr>
            @endif
        @else
            <tr>
                <td class="totals-label">Subtotal:</td>
                <td class="totals-value">${{ number_format($netAmount + $totalIva, 2, ',', '.') }}</td>
            </tr>
        @endif
        @if($invoice->percepciones > 0)
        <tr>
            <td class="totals-label">Percepciones:</td>
            <td class="totals-value">${{ number_format($invoice->percepciones, 2, ',', '.') }}</td>
        </tr>
        @endif
        @if($invoice->otros_impuestos > 0)
        <tr>
            <td class="totals-label">Otros Impuestos:</td>
            <td class="totals-value">${{ number_format($invoice->otros_impuestos, 2, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="totals-total">
            <td class="totals-label" style="font-weight: bold;">IMPORTE TOTAL: $</td>
            <td class="totals-value" style="font-size: 14px;">${{ number_format($netAmount + $totalIva + $invoice->percepciones + $invoice->otros_impuestos, 2, ',', '.') }}</td>
        </tr>
    </table>

    {{-- NOTAS --}}
    @if($invoice->notes)
        <div class="notes-box">
            <strong>Observaciones:</strong> {{ $invoice->notes }}
        </div>
    @endif

    {{-- CAE + QR (formato ARCA) --}}
    @if($invoice->cae)
        @php
            $barcode = $cuit
                . str_pad($afipCodes[$invoice->voucher_type] ?? '00', 3, '0', STR_PAD_LEFT)
                . str_pad($pos ? $pos->afip_pos_number : '1', 5, '0', STR_PAD_LEFT)
                . $invoice->cae
                . ($invoice->cae_expiration ? $invoice->cae_expiration->format('Ymd') : '');
            $sum_odd = 0; $sum_even = 0;
            for ($i = 0; $i < strlen($barcode); $i++) {
                if (($i + 1) % 2 === 0) { $sum_even += intval($barcode[$i]); }
                else { $sum_odd += intval($barcode[$i]); }
            }
            $digitoVerificador = (10 - (($sum_odd + $sum_even * 3) % 10)) % 10;
            $barcodeComplete = $barcode . $digitoVerificador;

            $qrData = json_encode([
                'ver' => 1,
                'fecha' => $invoice->issue_date->format('Y-m-d'),
                'cuit' => (int) $cuit,
                'ptoVta' => $pos ? $pos->afip_pos_number : 1,
                'tipoCmp' => (int) ($afipCodes[$invoice->voucher_type] ?? 0),
                'nroCmp' => (int) $invoice->afip_voucher_number,
                'importe' => round($netAmount + $totalIva + $invoice->percepciones + $invoice->otros_impuestos, 2),
                'moneda' => 'PES',
                'ctz' => 1,
                'tipoDocRec' => $customer->tax && strtolower($customer->tax) === 'consumidor final' ? 99 : 80,
                'nroDocRec' => (int) str_replace('-', '', $customer->taxId ?? '0'),
                'tipoCodAut' => 'E',
                'codAut' => (int) $invoice->cae,
            ]);
            $qrUrl = 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode($qrData);

            $qrRenderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $qrWriter = new \BaconQrCode\Writer($qrRenderer);
            $qrSvg = $qrWriter->writeString($qrUrl);
            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
        @endphp

        <div style="margin-top: 20px; border-top: 2px solid #333; padding-top: 12px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 130px; vertical-align: middle; text-align: center;">
                        <img src="{{ $qrDataUri }}" style="width: 110px; height: 110px;">
                    </td>
                    <td style="vertical-align: middle; padding-left: 15px;">
                        <div style="font-size: 14px; font-weight: bold; color: #1a1a1a; margin-bottom: 6px;">
                            ARCA
                            <span style="font-size: 9px; font-weight: normal; color: #555; margin-left: 8px;">AGENCIA DE RECAUDACIÓN Y CONTROL ADUANERO</span>
                        </div>
                        <div style="font-size: 11px; margin-bottom: 3px;">
                            <span style="color: #666;">CAE:</span>
                            <span style="font-weight: bold; font-family: 'Courier New', monospace;">{{ $invoice->cae }}</span>
                        </div>
                        <div style="font-size: 11px;">
                            <span style="color: #666;">Fecha de Vto. del CAE:</span>
                            <span style="font-weight: bold;">{{ $invoice->cae_expiration?->format('d/m/Y') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; margin-top: 12px;">
            <div style="font-family: 'Courier New', monospace; font-size: 10px; letter-spacing: 1px;">{{ $barcodeComplete }}</div>
        </div>
    @endif

    <div class="footer-line">
        {{ $company ? $company->name : config('afip.emisor.razon_social') }} — CUIT {{ $formattedCuit }} — {{ $company ? $company->tax_condition : config('afip.emisor.condicion_iva') }}<br>
        Comprobante generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
