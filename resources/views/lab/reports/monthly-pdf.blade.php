<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen {{ $insurance->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th { background: #0d9488; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.data th.right { text-align: right; }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        table.data td.right { text-align: right; }
        table.data td.codes { font-family: monospace; font-size: 8px; }
        table.data tfoot td { font-weight: bold; background: #ecfdf5; }
    </style>
    @include('partials.billing-summary-pdf-head')
</head>
<body>
    @include('partials.billing-summary-lab-header-pdf', [
        'reportTitle' => 'Facturación — '.$periodLabel,
        'counterpartyLabel' => 'Obra social: '.$insurance->billingDisplayName(),
    ])

    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>DNI</th>
                <th>Afiliado</th>
                <th>Determinaciones</th>
                <th class="right">Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['formatted_date'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['dni'] }}</td>
                    <td>{{ $row['affiliate'] }}</td>
                    <td class="codes">{{ $row['codes'] }}</td>
                    <td class="right">${{ number_format($row['price'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;">TOTAL ({{ $totals['protocol_count'] }} protocolos)</td>
                <td></td>
                <td class="right">${{ number_format($totals['total_amount'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
