<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen vet {{ $customer->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; }
        h1 { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #d97706; color: #fff; padding: 5px; text-align: left; font-size: 8px; }
        th.right { text-align: right; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        td.right { text-align: right; }
        td.codes { font-family: monospace; font-size: 8px; }
        tfoot td { font-weight: bold; background: #fffbeb; }
    </style>
</head>
<body>
    <h1>{{ $customer->name }}</h1>
    <p>Período: {{ $periodLabel }}</p>
    <table>
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
