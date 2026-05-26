<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen detallado {{ $insurance->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8px; color: #333; }
        h1 { font-size: 13px; margin-bottom: 2px; }
        .meta { font-size: 9px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d9488; color: #fff; padding: 4px 5px; text-align: left; font-size: 7px; text-transform: uppercase; }
        th.right { text-align: right; }
        td { padding: 3px 5px; border-bottom: 1px solid #eee; vertical-align: top; }
        td.right { text-align: right; }
        td.mono { font-family: monospace; }
        td.patient { white-space: pre-line; }
        tfoot td { font-weight: bold; background: #ecfdf5; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ strtoupper($insurance->name) }} — Resumen detallado</h1>
    <p class="meta">Período: {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>DNI</th>
                <th>Código</th>
                <th>Práctica</th>
                <th class="right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['formatted_date'] }}</td>
                    <td class="patient">{{ $row['patient_label'] }}</td>
                    <td>{{ $row['dni'] }}</td>
                    <td class="mono">{{ $row['code'] }}</td>
                    <td>{{ $row['practice'] }}</td>
                    <td class="right">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;">TOTAL A FACTURAR</td>
                <td>{{ ($totals['line_count'] ?? 0) }} prácticas · {{ $totals['protocol_count'] }} protocolos</td>
                <td class="right">${{ number_format($totals['total_amount'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
