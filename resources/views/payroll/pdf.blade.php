<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Sueldo - {{ $payroll->period_label }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        
        /* Header con logo */
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
            vertical-align: middle;
            text-align: right;
        }
        .logo {
            max-height: 60px;
            max-width: 200px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .company-subtitle {
            font-size: 10px;
            color: #666;
        }
        
        /* Título del recibo */
        .title-section {
            background-color: #2563eb;
            color: white;
            padding: 15px;
            margin-bottom: 15px;
        }
        .title-section h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .title-section .period {
            font-size: 12px;
            opacity: 0.9;
        }
        .title-section .convenio {
            font-size: 10px;
            opacity: 0.8;
            text-align: right;
        }
        
        /* Datos del empleado */
        .employee-data {
            display: table;
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            margin-bottom: 15px;
        }
        .employee-data .row {
            display: table-row;
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
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
        }
        
        /* Tablas de haberes y deducciones */
        .tables-container {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .table-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .table-column:first-child {
            padding-right: 10px;
        }
        .table-column:last-child {
            padding-left: 10px;
            border-left: 1px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            padding: 8px 0;
            border-bottom: 2px solid;
            margin-bottom: 10px;
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
        th {
            text-align: left;
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        th.right {
            text-align: right;
        }
        th.center {
            text-align: center;
        }
        td {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        td.right {
            text-align: right;
        }
        td.center {
            text-align: center;
        }
        .amount-positive {
            color: #16a34a;
            font-weight: 500;
        }
        .amount-negative {
            color: #dc2626;
            font-weight: 500;
        }
        .no-rem {
            color: #7c3aed;
            font-size: 10px;
        }
        
        /* Totales */
        .total-row td {
            padding-top: 10px;
            font-weight: bold;
            border-top: 2px solid #e2e8f0;
            border-bottom: none;
        }
        
        /* Neto */
        .neto-section {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .neto-container {
            display: table;
            width: 100%;
        }
        .neto-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }
        .neto-right {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
            text-align: right;
        }
        .neto-label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .neto-amount {
            font-size: 28px;
            font-weight: bold;
        }
        .neto-detail {
            font-size: 10px;
            opacity: 0.8;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
        
        /* Firma */
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            margin: 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header con Logo -->
        <div class="header">
            <div class="header-left">
                @if(file_exists(public_path('images/logo_ipac.png')))
                    <img src="{{ public_path('images/logo_ipac.png') }}" alt="Logo IPAC" class="logo">
                @else
                    <div class="company-name">{{ config('app.name', 'Labit') }}</div>
                @endif
                <div style="font-size: 9px; color: #666; margin-top: 5px;">DIRECCIÓN: LEGUIZAMON 356 ( 8300 ) Neuquén</div>
                <div style="font-size: 10px; font-weight: bold; color: #333;">CUIT: 27-29145034-8</div>
            </div>
            <div class="header-right">
                <div style="font-size: 9px; color: #666;">Período</div>
                <div style="font-weight: bold; font-size: 14px;">{{ $payroll->period_label }}</div>
            </div>
        </div>

        <!-- Título -->
        <div class="title-section">
            <table width="100%">
                <tr>
                    <td>
                        <h1>RECIBO DE SUELDO</h1>
                        <span class="period">{{ $payroll->period_label }}</span>
                    </td>
                    <td style="text-align: right;">
                        <div class="convenio">Convenio</div>
                        <div style="font-size: 11px;">CCT 108/75 (FATSA-CADIME/CEDIM)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Datos del Empleado -->
        <div class="employee-data">
            <table width="100%">
                <tr>
                    <td width="25%" style="padding: 5px;">
                        <div class="label">Apellido y Nombre:</div>
                        <div class="value">{{ $payroll->employee_name }}</div>
                    </td>
                    <td width="25%" style="padding: 5px;">
                        <div class="label">CUIL:</div>
                        <div class="value">{{ $payroll->employee_cuil }}</div>
                    </td>
                    <td width="25%" style="padding: 5px;">
                        <div class="label">Categoría:</div>
                        <div class="value">{{ $payroll->category_name }}</div>
                    </td>
                    <td width="25%" style="padding: 5px;">
                        <div class="label">Antigüedad:</div>
                        <div class="value">{{ $payroll->antiguedad_years }} años</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Haberes y Deducciones -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="48%" valign="top">
                    <!-- HABERES -->
                    <div class="section-title haberes">+ HABERES</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="center" width="60">%</th>
                                <th class="right" width="90">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payroll->haberes as $haber)
                                <tr>
                                    <td>
                                        {{ $haber->name }}
                                        @if(!$haber->is_remunerative)
                                            <span class="no-rem">(No Rem.)</span>
                                        @endif
                                    </td>
                                    <td class="center" style="color: #64748b;">{{ $haber->percentage }}</td>
                                    <td class="right amount-positive">${{ number_format($haber->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="2" style="color: #16a34a;">TOTAL HABERES</td>
                                <td class="right" style="color: #16a34a;">${{ number_format($payroll->total_haberes, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td width="4%"></td>
                <td width="48%" valign="top" style="border-left: 1px solid #e2e8f0; padding-left: 15px;">
                    <!-- DEDUCCIONES -->
                    <div class="section-title deducciones">− DEDUCCIONES</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="center" width="60">%</th>
                                <th class="right" width="90">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payroll->deducciones as $deduccion)
                                <tr>
                                    <td>{{ $deduccion->name }}</td>
                                    <td class="center" style="color: #64748b;">{{ $deduccion->percentage }}</td>
                                    <td class="right amount-negative">${{ number_format($deduccion->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="2" style="color: #dc2626;">TOTAL DEDUCCIONES</td>
                                <td class="right" style="color: #dc2626;">${{ number_format($payroll->total_deducciones, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Neto a Cobrar -->
        <div class="neto-section">
            <table width="100%">
                <tr>
                    <td width="50%" style="vertical-align: middle;">
                        <div style="font-size: 11px; opacity: 0.9; margin-bottom: 5px;">NETO A DEPOSITAR</div>
                        <div style="font-size: 26px; font-weight: bold;">${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}</div>
                    </td>
                    <td width="50%" style="text-align: right; vertical-align: middle;">
                        <div style="font-size: 10px; opacity: 0.9;">Total Haberes: ${{ number_format($payroll->total_haberes, 2, ',', '.') }}</div>
                        <div style="font-size: 10px; opacity: 0.9;">Total Deducciones: -${{ number_format($payroll->total_deducciones, 2, ',', '.') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Firma -->
        <div class="signature-section">
            <table width="100%">
                <tr>
                    <td width="45%" style="text-align: center; padding-top: 50px;">
                        <div class="signature-line">Firma del Empleado</div>
                    </td>
                    <td width="10%"></td>
                    <td width="45%" style="text-align: center; padding-top: 50px;">
                        <div class="signature-line">Firma del Empleador</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            
            @if($payroll->approvedBy)
                <p>Aprobado por: {{ $payroll->approvedBy->name }}</p>
            @endif
        </div>
    </div>
</body>
</html>







