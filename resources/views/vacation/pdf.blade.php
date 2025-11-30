<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pedido de Licencia - {{ $employee->lastName }} {{ $employee->name }}</title>
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
            padding: 20px 30px;
        }
        
        /* Header */
        .header {
            margin-bottom: 30px;
        }
        .header-logo {
            margin-bottom: 15px;
        }
        .header-title {
            text-align: right;
        }
        .logo-img {
            max-height: 90px;
            max-width: 350px;
        }
        .title-box {
            display: inline-block;
            background-color: #5a5a5a;
            color: white;
            padding: 10px 40px 10px 50px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-right: -30px;
        }
        
        /* Section Title */
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 25px 20px 15px 20px;
        }
        
        /* Table */
        table {
            width: calc(100% - 40px);
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-left: 20px;
            margin-right: 20px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
        }
        td {
            min-height: 25px;
        }
        .label-cell {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 180px;
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
        }
        .value-cell {
            background-color: white;
        }
        .empty-row td {
            height: 28px;
        }
        
        /* Info text */
        .info-text {
            font-size: 10px;
            color: #2dd4bf;
            margin: 20px 20px;
            font-style: italic;
        }
        
        /* Observations */
        .observations-box {
            border: 1px solid #999;
            padding: 10px;
            min-height: 80px;
            margin-bottom: 30px;
            margin-left: 20px;
            margin-right: 20px;
        }
        .observations-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        .observations-content {
            font-size: 11px;
            color: #333;
        }
        
        /* Signatures */
        .signatures {
            display: table;
            width: calc(100% - 40px);
            margin-top: 60px;
            margin-left: 20px;
            margin-right: 20px;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            vertical-align: bottom;
            text-align: center;
        }
        .signature-box:first-child {
            padding-right: 40px;
        }
        .signature-box:last-child {
            padding-left: 40px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            height: 60px;
            margin-bottom: 5px;
        }
        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }
        .signature-role {
            font-size: 10px;
            color: #666;
        }
        
        /* Footer */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 30px;
            right: 30px;
            font-size: 9px;
            color: #999;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-logo">
            <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC Laboratorio" class="logo-img">
        </div>
        <div class="header-title">
            <div class="title-box">PEDIDO DE LICENCIA</div>
        </div>
    </div>

    {{-- Section Title --}}
    <div class="section-title">LICENCIAS</div>

    {{-- Main Info Table --}}
    <table>
        <tr>
            <td class="label-cell">NOMBRE Y APELLIDO:</td>
            <td class="value-cell" colspan="3">{{ strtoupper($employee->lastName) }}, {{ ucwords($employee->name) }}</td>
        </tr>
        <tr>
            <td class="label-cell">FECHA DE AVISO:</td>
            <td class="value-cell" colspan="3">{{ $requestDate->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label-cell">MOTIVO:</td>
            <td class="value-cell" colspan="3">VACACIONES</td>
        </tr>
    </table>

    {{-- Dates Table --}}
    <table>
        <tr>
            <th style="width: 40%;">FECHA DE LA LICENCIA:</th>
            <th style="width: 30%;">DESDE:</th>
            <th style="width: 30%;">HASTA:</th>
        </tr>
        <tr>
            <td></td>
            <td>{{ \Carbon\Carbon::parse($leave->start)->format('d/m/Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($leave->end)->format('d/m/Y') }}</td>
        </tr>
        {{-- Empty rows for additional dates if needed --}}
        <tr class="empty-row"><td></td><td></td><td></td></tr>
        <tr class="empty-row"><td></td><td></td><td></td></tr>
    </table>

    {{-- Days Summary --}}
    <table>
        <tr>
            <td class="label-cell">TOTAL DÍAS SOLICITADOS:</td>
            <td class="value-cell" style="width: 100px; text-align: center; font-weight: bold;">{{ $leave->days }}</td>
            <td class="label-cell">DÍAS DISPONIBLES:</td>
            <td class="value-cell" style="width: 100px; text-align: center;">{{ $availableDays }}</td>
        </tr>
        <tr>
            <td class="label-cell">PUESTO:</td>
            <td class="value-cell" colspan="3">{{ $job ? strtoupper($job->name) : '—' }}</td>
        </tr>
        <tr>
            <td class="label-cell">CUIL:</td>
            <td class="value-cell">{{ $employee->employeeId }}</td>
            <td class="label-cell">LEGAJO:</td>
            <td class="value-cell">{{ $employee->id }}</td>
        </tr>
    </table>

    {{-- Info Text --}}
    <p class="info-text">
        El Responsable directo del personal EVIDENCIA con su firma la notificación de pedido y autorización de la licencia solicitada.
    </p>

    {{-- Observations --}}
    <div class="observations-box">
        <div class="observations-label">Observaciones:</div>
        <div class="observations-content">{{ $leave->description ?: '' }}</div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">{{ strtoupper($employee->lastName) }}, {{ ucwords($employee->name) }}</div>
            <div class="signature-role">Solicitante</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">
                @if($leave->status === 'aprobado' && $leave->approver)
                    {{ $leave->approver->name }}
                @else
                    Responsable de RRHH
                @endif
            </div>
            <div class="signature-role">Aprobado por</div>
        </div>
    </div>

    {{-- Status Badge --}}
    @if($leave->status !== 'pendiente')
        <div style="margin-top: 30px; text-align: center;">
            <span style="display: inline-block; padding: 8px 20px; border-radius: 5px; font-weight: bold; font-size: 12px;
                {{ $leave->status === 'aprobado' ? 'background-color: #dcfce7; color: #166534; border: 2px solid #86efac;' : 'background-color: #fee2e2; color: #991b1b; border: 2px solid #fca5a5;' }}">
                {{ strtoupper($leave->status) }}
                @if($leave->approved_at)
                    - {{ $leave->approved_at->format('d/m/Y') }}
                @endif
            </span>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        Solicitud N° {{ str_pad($leave->id, 6, '0', STR_PAD_LEFT) }} | Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
