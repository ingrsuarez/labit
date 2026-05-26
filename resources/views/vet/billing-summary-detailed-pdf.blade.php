<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen detallado vet {{ $customer->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8px; color: #333; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th { background: #d97706; color: #fff; padding: 4px 5px; text-align: left; font-size: 7px; text-transform: uppercase; }
        table.data th.right { text-align: right; }
        table.data td { padding: 3px 5px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.data td.right { text-align: right; }
        table.data td.mono { font-family: monospace; }
        table.data tfoot td { font-weight: bold; background: #fffbeb; font-size: 9px; }
    </style>
    @include('partials.billing-summary-pdf-head')
</head>
<body>
    @include('partials.billing-summary-lab-header-pdf', [
        'reportTitle' => 'Facturación detallada veterinaria — '.$periodLabel,
        'counterpartyLabel' => 'Cliente: '.$customer->displayName(),
    ])

    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Animal</th>
                <th>Código</th>
                <th>Práctica</th>
                <th class="right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['formatted_date'] }}</td>
                    <td>{{ $row['subject_label'] }}</td>
                    <td class="mono">{{ $row['code'] }}</td>
                    <td>{{ $row['practice'] }}</td>
                    <td class="right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right;">TOTAL A FACTURAR</td>
                <td>{{ ($totals['line_count'] ?? 0) }} prácticas · {{ $totals['protocol_count'] }} protocolos</td>
                <td class="right">${{ number_format($totals['total_amount'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
