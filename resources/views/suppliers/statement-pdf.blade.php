<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; }
    .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
    .header h1 { font-size: 16px; margin: 0 0 4px; }
    .header-grid { display: table; width: 100%; }
    .header-left { display: table-cell; width: 60%; vertical-align: top; }
    .header-right { display: table-cell; width: 40%; text-align: right; color: #666; vertical-align: top; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background-color: #f3f4f6; border: 1px solid #e5e7eb; padding: 6px 8px;
         text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; }
    th.num { text-align: right; }
    td { border: 1px solid #e5e7eb; padding: 5px 8px; vertical-align: middle; }
    td.num { text-align: right; font-family: monospace; }
    .row-saldo-inicial { background-color: #f9fafb; color: #9ca3af; font-style: italic; }
    .row-payment { background-color: #f0fdf4; }
    tfoot tr { background-color: #f3f4f6; font-weight: bold; }
    .saldo-final { font-size: 12px; font-weight: bold; }
    .badge-acreedor { color: #16a34a; }
    .badge-deudor { color: #ea580c; }
    .footer { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-grid">
        <div class="header-left">
            <h1>Cuenta Corriente de Proveedor</h1>
            <p style="margin:4px 0 0; font-size:12px; font-weight:bold;">{{ $supplier->name }}</p>
            @if($supplier->tax_id)
            <p style="margin:2px 0 0; color:#6b7280;">CUIT: {{ $supplier->tax_id }}</p>
            @endif
        </div>
        <div class="header-right">
            <p style="margin:0;"><strong>{{ $company->name ?? 'Empresa' }}</strong></p>
            <p style="margin:2px 0 0;">Período: {{ $dateFromCarbon->format('d/m/Y') }} al {{ $dateToCarbon->format('d/m/Y') }}</p>
            <p style="margin:2px 0 0;">Generado: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:10%">Fecha</th>
            <th style="width:22%">Comprobante</th>
            <th style="width:28%">Detalle</th>
            <th class="num" style="width:13%">Debe</th>
            <th class="num" style="width:13%">Haber</th>
            <th class="num" style="width:14%">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr class="row-saldo-inicial">
            <td>—</td>
            <td>Saldo anterior</td>
            <td>Movimientos previos al período</td>
            <td class="num"></td>
            <td class="num"></td>
            <td class="num">
                $ {{ number_format(abs($openBalance), 2, ',', '.') }}
                @if($openBalance != 0)
                <span class="{{ $openBalance > 0 ? 'badge-acreedor' : 'badge-deudor' }}">
                    {{ $openBalance > 0 ? 'AC' : 'AD' }}
                </span>
                @endif
            </td>
        </tr>
        @foreach($movements as $mov)
        <tr class="row-{{ $mov['type'] }}">
            <td>{{ $mov['date']->format('d/m/Y') }}</td>
            <td>{{ $mov['reference'] }}</td>
            <td>{{ $mov['detail'] }}</td>
            <td class="num">{{ $mov['debe'] > 0 ? '$ '.number_format($mov['debe'], 2, ',', '.') : '' }}</td>
            <td class="num">{{ $mov['haber'] > 0 ? '$ '.number_format($mov['haber'], 2, ',', '.') : '' }}</td>
            <td class="num">
                $ {{ number_format(abs($mov['saldo']), 2, ',', '.') }}
                <span class="{{ $mov['saldo'] >= 0 ? 'badge-acreedor' : 'badge-deudor' }}">
                    {{ $mov['saldo'] >= 0 ? 'AC' : 'AD' }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">Totales del período</td>
            <td class="num">$ {{ number_format($totalDebe, 2, ',', '.') }}</td>
            <td class="num">$ {{ number_format($totalHaber, 2, ',', '.') }}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="5" style="text-align:right; font-size:11px;">
                Saldo al {{ $dateToCarbon->format('d/m/Y') }}
            </td>
            <td class="num saldo-final">
                $ {{ number_format(abs($closeBalance), 2, ',', '.') }}
                <span class="{{ $closeBalance >= 0 ? 'badge-acreedor' : 'badge-deudor' }}">
                    {{ $closeBalance >= 0 ? 'Acreedor' : 'Deudor' }}
                </span>
            </td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    AC = Saldo Acreedor (deuda con el proveedor) — AD = Saldo Deudor (anticipo o pago en exceso)
</div>

</body>
</html>
