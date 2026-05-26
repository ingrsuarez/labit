<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen {{ $insurance->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d9488; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 4px 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        td.right { text-align: right; }
        td.codes { font-family: monospace; font-size: 8px; }
        tfoot td { font-weight: bold; background: #ecfdf5; }
    </style>
</head>
<body>
    <h1>{{ strtoupper($insurance->name) }}</h1>
    <p class="meta">Período: {{ $periodLabel }}</p>

    <table>
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
