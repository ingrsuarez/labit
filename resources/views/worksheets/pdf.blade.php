<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 8px;
        }
        .header h1 {
            font-size: 14pt;
            margin: 0 0 4px 0;
            color: #0d9488;
        }
        .header p {
            font-size: 9pt;
            margin: 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #999;
            padding: 3px 4px;
            text-align: center;
        }
        th {
            background-color: #f0fdfa;
            font-size: 8pt;
            font-weight: bold;
            color: #0d9488;
        }
        th.protocol-col, th.name-col {
            text-align: left;
            background-color: #e5e7eb;
            color: #333;
        }
        th.protocol-col {
            width: 80px;
        }
        th.name-col {
            width: 120px;
        }
        th.test-col {
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-size: 7pt;
        }
        td.protocol-col {
            text-align: left;
            white-space: nowrap;
            font-weight: bold;
        }
        td.name-col {
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        td.cell-pending {
            color: #0d9488;
            font-weight: bold;
            font-size: 11pt;
        }
        td.cell-not-ordered {
            background: repeating-linear-gradient(
                45deg,
                #f5f5f5,
                #f5f5f5 3px,
                #e5e7eb 3px,
                #e5e7eb 6px
            );
            color: #aaa;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 7pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $worksheet->name }}</h1>
        <p>
            {{ $worksheet->type === 'clinico' ? 'Laboratorio Clínico' : 'Aguas y Alimentos' }}
            &nbsp;|&nbsp;
            Período: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            &nbsp;|&nbsp;
            Generado: {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>

    @if($rows->isEmpty())
        <div class="no-data">No se encontraron protocolos con los filtros seleccionados.</div>
    @else
    <table>
        <thead>
            <tr>
                <th class="protocol-col">N° Prot.</th>
                <th class="name-col">{{ $worksheet->type === 'clinico' ? 'Paciente' : 'Cliente' }}</th>
                @foreach($tests as $test)
                <th class="test-col">{{ $test->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td class="protocol-col">{{ $row['protocol'] }}</td>
                <td class="name-col">{{ $row['name'] }}</td>
                @foreach($tests as $test)
                @php $val = $row['results'][$test->id] ?? null; @endphp
                @if($val === null)
                    <td class="cell-not-ordered"></td>
                @elseif($val === '')
                    <td class="cell-pending">✓</td>
                @else
                    <td>{{ $val }}</td>
                @endif
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
