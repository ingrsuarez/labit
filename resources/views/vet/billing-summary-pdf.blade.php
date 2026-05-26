<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen vet {{ $customer->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th { background: #d97706; color: #fff; padding: 5px; text-align: left; font-size: 8px; }
        table.data th.right { text-align: right; }
        table.data td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        table.data td.right { text-align: right; }
        table.data td.codes { font-family: monospace; font-size: 8px; }
        table.data tfoot td { font-weight: bold; background: #fffbeb; }
    </style>
    @include('partials.billing-summary-pdf-head')
</head>
<body>
    @include('partials.billing-summary-lab-header-pdf', [
        'reportTitle' => 'Facturación veterinaria — '.$periodLabel,
        'counterpartyLabel' => 'Cliente: '.$customer->displayName(),
    ])

    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Animal</th>
                <th>Determinaciones</th>
                <th class="right">Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['formatted_date'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="codes">{{ $row['codes'] }}</td>
                    <td class="right">${{ number_format($row['price'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;">TOTAL ({{ $totals['protocol_count'] }} protocolos)</td>
                <td></td>
                <td class="right">${{ number_format($totals['total_amount'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
