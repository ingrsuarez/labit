<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Novedades de Empleados</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .filters {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .filters span {
            margin-right: 20px;
        }
        .period-header {
            background: #3B82F6;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #dbeafe;
            color: #1e40af;
            font-weight: bold;
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #93c5fd;
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            padding: 6px 5px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        tr:hover {
            background: #eff6ff;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-vacaciones {
            background: #dcfce7;
            color: #166534;
        }
        .badge-enfermedad {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-embarazo {
            background: #fce7f3;
            color: #9d174d;
        }
        .badge-horas {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-capacitacion {
            background: #e0e7ff;
            color: #3730a3;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .summary {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .summary-title {
            font-weight: bold;
            color: #0369a1;
            margin-bottom: 5px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #0369a1;
        }
        .summary-label {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Novedades de Empleados para Liquidación</h1>
        @if($filters['year'] && $filters['month'])
            <p style="font-size: 14px; color: #1e40af; font-weight: bold;">
                Período: {{ str_pad($filters['month'], 2, '0', STR_PAD_LEFT) }}/{{ $filters['year'] }}
            </p>
        @elseif($filters['year'])
            <p style="font-size: 14px; color: #1e40af; font-weight: bold;">
                Año: {{ $filters['year'] }}
            </p>
        @endif
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if($filters['employee_id'])
    <div class="filters">
        <strong>Filtro adicional:</strong>
        <span>Empleado ID: {{ $filters['employee_id'] }}</span>
    </div>
    @endif

    @php
        $grouped = $resumes->groupBy(fn($r) => sprintf('%04d-%02d', $r->year, $r->month));
        $totalDias = $resumes->sum('total_dias');
        $totalH50 = $resumes->sum('horas_50');
        $totalH100 = $resumes->sum('horas_100');
    @endphp

    {{-- Resumen General --}}
    <div class="summary">
        <div class="summary-title">Resumen General</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ $resumes->count() }}</div>
                <div class="summary-label">Total Registros</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ (int)$totalDias }}</div>
                <div class="summary-label">Total Días</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ (int)$totalH50 }}</div>
                <div class="summary-label">Horas 50%</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ (int)$totalH100 }}</div>
                <div class="summary-label">Horas 100%</div>
            </div>
        </div>
    </div>

    @forelse($grouped as $ym => $rows)
        <div class="period-header">Período: {{ $ym }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%">Empleado</th>
                    <th style="width: 12%">CUIL</th>
                    <th style="width: 8%" class="text-center">Hs. Sem.</th>
                    <th style="width: 12%">Categoría</th>
                    <th style="width: 12%">Tipo</th>
                    <th style="width: 7%" class="text-right">Cant.</th>
                    <th style="width: 9%" class="text-right">Días</th>
                    <th style="width: 9%" class="text-right">Hs 50%</th>
                    <th style="width: 9%" class="text-right">Hs 100%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td>{{ $r->employee }}</td>
                    <td>{{ $r->cuil ?? '—' }}</td>
                    <td class="text-center">{{ (int)($r->weekly_hours ?? 0) }}</td>
                    <td>{{ $r->category ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match($r->type) {
                                'vacaciones' => 'badge-vacaciones',
                                'enfermedad' => 'badge-enfermedad',
                                'embarazo' => 'badge-embarazo',
                                'horas extra' => 'badge-horas',
                                'capacitacion' => 'badge-capacitacion',
                                default => ''
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($r->type) }}</span>
                    </td>
                    <td class="text-right">{{ $r->cantidad }}</td>
                    <td class="text-right">{{ (int)$r->total_dias }}</td>
                    <td class="text-right">{{ (int)$r->horas_50 }}</td>
                    <td class="text-right">{{ (int)$r->horas_100 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p style="text-align: center; padding: 20px; color: #666;">No hay novedades para el criterio seleccionado.</p>
    @endforelse

    <div class="footer">
        Sistema de Gestión de Recursos Humanos - {{ config('app.name', 'IPAC') }}
    </div>
</body>
</html>

