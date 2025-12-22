<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Novedades para Liquidación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #6b7280;
        }
        .header .period {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-top: 8px;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 9px;
            color: #6b7280;
        }
        .section-title {
            background-color: #2563eb;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: #e5e7eb;
        }
        th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #d1d5db;
        }
        th.right {
            text-align: right;
        }
        th.center {
            text-align: center;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        td.right {
            text-align: right;
        }
        td.center {
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-vacaciones {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        .badge-enfermedad {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .badge-embarazo {
            background-color: #fce7f3;
            color: #db2777;
        }
        .badge-capacitacion {
            background-color: #d1fae5;
            color: #059669;
        }
        .badge-horas {
            background-color: #fef3c7;
            color: #d97706;
        }
        .period-header {
            background-color: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
            color: #1f2937;
            border-left: 4px solid #2563eb;
            margin-top: 15px;
            margin-bottom: 0;
        }
        .summary-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 12px;
            margin-top: 20px;
        }
        .summary-box h3 {
            font-size: 11px;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 8px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 12px;
        }
        .highlight {
            font-weight: bold;
            color: #1e40af;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>RESUMEN DE NOVEDADES PARA LIQUIDACIÓN</h1>
            <div class="subtitle">{{ config('app.name', 'Sistema de Gestión') }}</div>
            @php
                $mesesPdf = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];
            @endphp
            @if($filters['year'] && $filters['month'])
                <div class="period">
                    Período: {{ $mesesPdf[(int)$filters['month']] }} {{ $filters['year'] }}
                </div>
            @elseif($filters['year'])
                <div class="period">Año: {{ $filters['year'] }}</div>
            @else
                <div class="period">Todos los períodos</div>
            @endif
        </div>

        {{-- Meta info --}}
        <div class="meta">
            <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
            <span>Total registros: {{ $resumes->count() }}</span>
        </div>

        @if($resumes->isEmpty())
            <div class="no-data">
                No hay novedades para el período seleccionado.
            </div>
        @else
            {{-- Agrupar por período --}}
            @php
                $grouped = $resumes->groupBy(fn($r) => sprintf('%04d-%02d', $r->year, $r->month));
                $totals = [
                    'vacaciones' => 0,
                    'enfermedad' => 0,
                    'embarazo' => 0,
                    'horas_50' => 0,
                    'horas_100' => 0,
                ];
            @endphp

            @foreach($grouped as $ym => $rows)
                @php
                    $mesPdfNum = (int)\Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m');
                    $anioPdf = \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('Y');
                    $mesesPdfNombres = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ];
                @endphp
                <div class="period-header">
                    Período: {{ $mesesPdfNombres[$mesPdfNum] }} {{ $anioPdf }}
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Empleado</th>
                            <th style="width: 12%;">CUIL</th>
                            <th class="center" style="width: 8%;">Hs/Sem</th>
                            <th style="width: 12%;">Tipo</th>
                            <th class="right" style="width: 8%;">Cant.</th>
                            <th class="right" style="width: 10%;">Días</th>
                            <th class="right" style="width: 10%;">Hs 50%</th>
                            <th class="right" style="width: 10%;">Hs 100%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            @php
                                // Acumular totales
                                if ($r->type === 'vacaciones') $totals['vacaciones'] += (int)$r->total_dias;
                                if ($r->type === 'enfermedad') $totals['enfermedad'] += (int)$r->total_dias;
                                if ($r->type === 'embarazo') $totals['embarazo'] += (int)$r->total_dias;
                                $totals['horas_50'] += (int)$r->horas_50;
                                $totals['horas_100'] += (int)$r->horas_100;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $r->employee }}</strong>
                                </td>
                                <td>{{ $r->cuil ?? '—' }}</td>
                                <td class="center">{{ (int)($r->weekly_hours ?? 0) }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($r->type) {
                                            'vacaciones' => 'badge-vacaciones',
                                            'enfermedad' => 'badge-enfermedad',
                                            'embarazo' => 'badge-embarazo',
                                            'capacitacion' => 'badge-capacitacion',
                                            'horas extra' => 'badge-horas',
                                            default => ''
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($r->type) }}</span>
                                </td>
                                <td class="right">{{ $r->cantidad }}</td>
                                <td class="right highlight">{{ (int)$r->total_dias }}</td>
                                <td class="right">{{ (int)$r->horas_50 > 0 ? (int)$r->horas_50 : '—' }}</td>
                                <td class="right">{{ (int)$r->horas_100 > 0 ? (int)$r->horas_100 : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

            {{-- Resumen de totales --}}
            <div class="summary-box">
                <h3>RESUMEN TOTALES</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value">{{ $totals['vacaciones'] }}</div>
                        <div class="summary-label">Días Vacaciones</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $totals['enfermedad'] }}</div>
                        <div class="summary-label">Días Enfermedad</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $totals['embarazo'] }}</div>
                        <div class="summary-label">Días Embarazo</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $totals['horas_50'] }}</div>
                        <div class="summary-label">Horas Extra 50%</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $totals['horas_100'] }}</div>
                        <div class="summary-label">Horas Extra 100%</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>Documento generado automáticamente por {{ config('app.name', 'Sistema de Gestión') }}</p>
            <p>Este documento es para uso interno y cálculo de liquidaciones de sueldos.</p>
        </div>
    </div>
</body>
</html>

