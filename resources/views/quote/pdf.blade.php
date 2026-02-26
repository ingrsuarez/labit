<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto {{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }

        .info-bar {
            background-color: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .info-bar td { vertical-align: top; padding: 2px 0; }
        .info-label { color: #666; font-size: 11px; }
        .info-value { color: #000; font-weight: bold; font-size: 12px; }

        .quote-number {
            font-size: 18px;
            font-weight: bold;
            color: #00838f;
            margin-bottom: 15px;
        }

        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th {
            background-color: #00838f;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table th.right { text-align: right; }
        .items-table th.center { text-align: center; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
        .items-table td.right { text-align: right; }
        .items-table td.center { text-align: center; }
        .items-table tr:nth-child(even) { background-color: #fafafa; }
        .items-table .item-num { color: #999; }

        .totals-table { width: 250px; margin-left: auto; margin-top: 10px; }
        .totals-table td { padding: 5px 10px; font-size: 12px; }
        .totals-table .label { text-align: right; color: #666; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .totals-table .total-row td {
            border-top: 2px solid #00838f;
            padding-top: 8px;
            font-size: 14px;
            color: #00838f;
        }

        .notes-block {
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #fffde7;
            border-left: 3px solid #ffc107;
            font-size: 11px;
        }
        .notes-title { font-weight: bold; color: #00838f; margin-bottom: 5px; text-transform: uppercase; }

        .validity {
            margin-top: 15px;
            padding: 8px 15px;
            background-color: #e0f2f1;
            border-radius: 4px;
            font-size: 11px;
            color: #00695c;
        }

        .footer-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            font-size: 11px;
        }
        .footer-section td { vertical-align: bottom; }

        .signature-line {
            width: 150px;
            border-top: 1px solid #333;
            margin-left: auto;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <htmlpageheader name="quoteHeader">
        <table width="100%" style="border-bottom: 2px solid #030303; padding-bottom: 10px;">
            <tr>
                <td width="30%" style="vertical-align: middle;">
                    <img src="{{ public_path('images/logo_ipac.png') }}" style="max-height: 60px; max-width: 150px;">
                </td>
                <td width="70%" style="vertical-align: middle; text-align: right;">
                    <span style="font-size: 20px; font-weight: bold; color: #C3C3C3;">IPAC Laboratorio de Aguas y Alimentos</span>
                </td>
            </tr>
        </table>
    </htmlpageheader>
    <sethtmlpageheader name="quoteHeader" value="on" show-this-page="1" />

    <htmlpagefooter name="quoteFooter">
        <div style="font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 5px;">
            {{ $quote->quote_number }} | Página {PAGENO} de {nbpg} | Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="quoteFooter" value="on" />

    <!-- Número de presupuesto -->
    <div class="quote-number">PRESUPUESTO {{ $quote->quote_number }}</div>

    <!-- Info del cliente -->
    <div class="info-bar">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">{{ strtoupper($quote->customer_name) }}</div>
                    @if($quote->customer_email)
                        <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $quote->customer_email }}</div>
                    @endif
                    @if($quote->customer && $quote->customer->address)
                        <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $quote->customer->address }}</div>
                    @endif
                </td>
                <td width="40%" style="text-align: right;">
                    <div class="info-label">Fecha</div>
                    <div class="info-value">{{ $quote->created_at->format('d/m/Y') }}</div>
                    @if($quote->valid_until)
                        <div style="font-size: 11px; color: #666; margin-top: 4px;">
                            Válido hasta: <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de ítems -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="center">#</th>
                <th>Descripción</th>
                <th class="center" style="width: 60px;">Cant.</th>
                <th class="right" style="width: 100px;">P. Unitario</th>
                <th class="right" style="width: 100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $index => $item)
                <tr>
                    <td class="center item-num">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="right" style="font-weight: bold;">${{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">${{ number_format($quote->subtotal, 2, ',', '.') }}</td>
        </tr>
        @if($quote->tax_rate > 0)
            <tr>
                <td class="label">IVA ({{ $quote->tax_rate }}%)</td>
                <td class="value">${{ number_format($quote->tax_amount, 2, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="label" style="font-weight: bold;">TOTAL</td>
            <td class="value">${{ number_format($quote->total, 2, ',', '.') }}</td>
        </tr>
    </table>

    @if($quote->valid_until)
        <div class="validity">
            Este presupuesto tiene validez hasta el <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>.
        </div>
    @endif

    @if($quote->notes)
        <div class="notes-block">
            <div class="notes-title">Notas</div>
            <p>{{ $quote->notes }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer-section">
        <table width="100%">
            <tr>
                <td width="50%">
                    <div style="font-weight: bold;">IPAC Laboratorio</div>
                    <div>Leguizamón 356 - Neuquén</div>
                    <div>TEL: 0299-6227547</div>
                    <div>www.ipac.com.ar</div>
                </td>
                <td width="50%" style="text-align: right;">
                    <div class="signature-line">
                        <div style="font-weight: bold; font-size: 12px;">Firma / Sello</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
