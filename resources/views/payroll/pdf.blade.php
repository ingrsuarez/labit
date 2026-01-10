<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Sueldo - {{ $payroll->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            max-width: 100%;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #666;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .company-sub {
            font-size: 10px;
            color: #666;
        }
        .title-bar {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            color: white;
            padding: 15px 20px;
            margin-bottom: 15px;
        }
        .title-bar h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .title-bar .period {
            font-size: 12px;
            opacity: 0.9;
        }
        .title-bar .convenio {
            float: right;
            text-align: right;
            font-size: 10px;
        }
        .employee-data {
            display: table;
            width: 100%;
            background: #f8fafc;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .employee-data .cell {
            display: table-cell;
            width: 25%;
            padding: 5px;
        }
        .employee-data .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
        }
        .employee-data .value {
            font-weight: bold;
            font-size: 11px;
        }
        .columns {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .column:first-child {
            border-right: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid;
        }
        .section-title.haberes {
            color: #16a34a;
            border-color: #16a34a;
        }
        .section-title.deducciones {
            color: #dc2626;
            border-color: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            text-align: left;
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        table th.right {
            text-align: right;
        }
        table th.center {
            text-align: center;
        }
        table td {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        table td.right {
            text-align: right;
        }
        table td.center {
            text-align: center;
        }
        .amount-green {
            color: #16a34a;
            font-weight: 600;
        }
        .amount-red {
            color: #dc2626;
            font-weight: 600;
        }
        .amount-purple {
            color: #7c3aed;
        }
        .total-row {
            font-weight: bold;
            border-top: 2px solid #e2e8f0;
        }
        .total-row td {
            padding-top: 10px;
        }
        .neto-bar {
            background-color: #1d4ed8;
            color: white;
            padding: 20px;
            margin-top: 15px;
            width: 100%;
            overflow: hidden;
        }
        .neto-left {
            float: left;
            width: 55%;
        }
        .neto-right {
            float: right;
            width: 40%;
            text-align: right;
            font-size: 10px;
        }
        .neto-label {
            font-size: 10px;
            opacity: 0.8;
        }
        .neto-amount {
            font-size: 28px;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
        .no-rem {
            color: #7c3aed;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC" style="height: 50px;">
            </div>
            <div class="header-right">
                <p style="font-size: 14px; font-weight: bold;">{{ ucfirst(\Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->locale('es')->translatedFormat('F Y')) }}</p>
            </div>
        </div>

        {{-- Title Bar --}}
        <div class="title-bar">
            <div class="convenio">
                <div style="opacity: 0.8;">Convenio</div>
                <div style="font-weight: bold;">CCT 108/75 (FATSA-CADIME/CEDIM)</div>
            </div>
            <h1>RECIBO DE SUELDO</h1>
            <div class="period">{{ ucfirst(\Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->locale('es')->translatedFormat('F Y')) }}</div>
        </div>

        {{-- Employee Data --}}
        <div class="employee-data">
            <div class="cell">
                <div class="label">Empleado:</div>
                <div class="value">{{ $payroll->employee_name }}</div>
            </div>
            <div class="cell">
                <div class="label">CUIL:</div>
                <div class="value">{{ $payroll->employee_cuil }}</div>
            </div>
            <div class="cell">
                <div class="label">Categoría:</div>
                <div class="value">{{ $payroll->category_name }}</div>
            </div>
            <div class="cell">
                <div class="label">Antigüedad:</div>
                <div class="value">{{ $payroll->antiguedad_years }} años</div>
            </div>
        </div>

        {{-- Haberes y Deducciones --}}
        <div class="columns">
            {{-- Haberes --}}
            <div class="column">
                <div class="section-title haberes">+ HABERES</div>
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="center">%</th>
                            <th class="right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payroll->haberes as $haber)
                            <tr>
                                <td class="{{ !$haber->is_remunerative ? 'amount-purple' : '' }}">
                                    {{ $haber->name }}
                                    @if(!$haber->is_remunerative)
                                        <span class="no-rem">(No Rem.)</span>
                                    @endif
                                </td>
                                <td class="center" style="color: #64748b;">{{ $haber->percentage }}</td>
                                <td class="right amount-green">${{ number_format($haber->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td style="color: #15803d;">TOTAL HABERES</td>
                            <td></td>
                            <td class="right" style="color: #15803d;">${{ number_format($payroll->total_haberes, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Deducciones --}}
            <div class="column">
                <div class="section-title deducciones">− DEDUCCIONES</div>
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="center">%</th>
                            <th class="right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payroll->deducciones as $deduccion)
                            <tr>
                                <td>{{ $deduccion->name }}</td>
                                <td class="center" style="color: #64748b;">{{ $deduccion->percentage }}</td>
                                <td class="right amount-red">${{ number_format($deduccion->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td style="color: #b91c1c;">TOTAL DEDUCCIONES</td>
                            <td></td>
                            <td class="right" style="color: #b91c1c;">${{ number_format($payroll->total_deducciones, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Neto a Cobrar --}}
        <div style="background-color: #1d4ed8; color: white; padding: 15px 20px; margin-top: 15px; width: 100%;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%; vertical-align: middle;">
                        <span style="font-size: 10px; opacity: 0.9;">NETO A COBRAR</span><br>
                        <span style="font-size: 24px; font-weight: bold;">${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}</span>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: middle; font-size: 10px;">
                        <span>Bruto: ${{ number_format($payroll->total_haberes, 2, ',', '.') }}</span><br>
                        <span>Deducciones: -${{ number_format($payroll->total_deducciones, 2, ',', '.') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Footer con Firmas --}}
        <div style="margin-top: 40px; display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; text-align: center; padding: 0 30px;">
                <div style="border-top: 1px solid #333; margin-top: 50px; padding-top: 8px;">
                    <p style="font-size: 11px; font-weight: bold;">Firma Empleador</p>
                </div>
            </div>
            <div style="display: table-cell; width: 50%; text-align: center; padding: 0 30px;">
                <div style="border-top: 1px solid #333; margin-top: 50px; padding-top: 8px;">
                    <p style="font-size: 11px; font-weight: bold;">Firma Empleado</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
