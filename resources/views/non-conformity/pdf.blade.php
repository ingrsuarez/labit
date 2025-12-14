<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Conformidad {{ $nonConformity->code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 30px;
        }

        /* Header fijo en todas las páginas */
        .header-fixed {
            position: fixed;
            top: 0;
            left: 30px;
            right: 30px;
            display: table;
            width: calc(100% - 60px);
            border-bottom: 2px solid #333;
            padding: 10px 0;
            padding-bottom: 10px;
            background: #fff;
            z-index: 100;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            width: 30%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 20%;
        }

        .logo-img {
            max-height: 50px;
            max-width: 120px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-left: 10px;
            display: inline-block;
            vertical-align: middle;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }

        .code-box {
            background: #f3f4f6;
            border: 2px solid #333;
            padding: 5px 15px;
            text-align: center;
            display: inline-block;
        }

        .code-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }

        .code-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        /* Espacio para el header fijo */
        .content {
            margin-top: 100px;
            padding-top: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
            color: #666;
            background: #f9fafb;
        }

        .info-value {
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-leve { background: #e5e7eb; color: #374151; }
        .badge-moderada { background: #d1d5db; color: #1f2937; }
        .badge-grave { background: #9ca3af; color: #111827; }

        .badge-abierta { background: #e5e7eb; color: #374151; }
        .badge-en_proceso { background: #d1d5db; color: #1f2937; }
        .badge-cerrada { background: #9ca3af; color: #111827; }

        .text-block {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 12px;
            border-radius: 4px;
            margin-top: 5px;
            min-height: 60px;
        }

        .text-block.empty {
            color: #9ca3af;
            font-style: italic;
        }

        .footer {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .employee-data {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 15px;
            margin-bottom: 30px;
        }

        .employee-title {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .employee-info {
            display: table;
            width: 100%;
        }

        .employee-row {
            display: table-row;
        }

        .employee-cell {
            display: table-cell;
            padding: 3px 0;
        }

        .employee-label {
            width: 120px;
            color: #6b7280;
        }

        .employee-value {
            font-weight: bold;
            color: #111827;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 10px;
        }

        .signature-label {
            font-size: 11px;
            color: #666;
        }

        .signature-name {
            font-weight: bold;
            font-size: 12px;
        }

        .page-footer {
            position: fixed;
            bottom: 20px;
            left: 30px;
            right: 30px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        @media print {
            body { padding: 20px; }
            .page-footer { position: fixed; }
        }
    </style>
</head>
<body>
    <!-- Header fijo en todas las páginas -->
    <div class="header-fixed">
        <div class="header-left">
            <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC" class="logo-img">
            <span class="company-name">IPAC Laboratorio</span>
        </div>
        <div class="header-center">
            <div class="document-title">Registro de No Conformidad</div>
        </div>
        <div class="header-right">
            <div class="code-box">
                <div class="code-label">Código</div>
                <div class="code-value">{{ $nonConformity->code }}</div>
            </div>
        </div>
    </div>

    <!-- Contenido con margen para el header fijo -->
    <div class="content">

    <!-- Información General -->
    <div class="section">
        <div class="section-title">Información General</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell info-label">Fecha del Incidente</div>
                <div class="info-cell info-value">{{ $nonConformity->date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Tipo</div>
                <div class="info-cell info-value">{{ \App\Models\NonConformity::types()[$nonConformity->type] ?? $nonConformity->type }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Severidad</div>
                <div class="info-cell info-value">
                    <span class="badge badge-{{ $nonConformity->severity }}">{{ ucfirst($nonConformity->severity) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Estado</div>
                <div class="info-cell info-value">
                    <span class="badge badge-{{ $nonConformity->status }}">{{ ucfirst(str_replace('_', ' ', $nonConformity->status)) }}</span>
                </div>
            </div>
            @if($nonConformity->procedure_name)
            <div class="info-row">
                <div class="info-cell info-label">Procedimiento</div>
                <div class="info-cell info-value">{{ $nonConformity->procedure_name }}</div>
            </div>
            @endif
            @if($nonConformity->training_name)
            <div class="info-row">
                <div class="info-cell info-label">Capacitación</div>
                <div class="info-cell info-value">{{ $nonConformity->training_name }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-cell info-label">Reportado por</div>
                <div class="info-cell info-value">{{ $nonConformity->reporter->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Fecha de Registro</div>
                <div class="info-cell info-value">{{ $nonConformity->created_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($nonConformity->status === 'cerrada' && $nonConformity->closed_at)
            <div class="info-row">
                <div class="info-cell info-label">Cerrado por</div>
                <div class="info-cell info-value">{{ $nonConformity->closer->name ?? 'N/A' }} - {{ $nonConformity->closed_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Descripción -->
    <div class="section">
        <div class="section-title">Descripción del Incidente</div>
        <div class="text-block">{{ $nonConformity->description }}</div>
    </div>

    <!-- Acción Correctiva -->
    <div class="section">
        <div class="section-title">Acción Correctiva</div>
        <div class="text-block {{ empty($nonConformity->corrective_action) ? 'empty' : '' }}">
            {{ $nonConformity->corrective_action ?: 'Sin acción correctiva registrada' }}
        </div>
    </div>

    <!-- Acción Preventiva -->
    <div class="section">
        <div class="section-title">Acción Preventiva</div>
        <div class="text-block {{ empty($nonConformity->preventive_action) ? 'empty' : '' }}">
            {{ $nonConformity->preventive_action ?: 'Sin acción preventiva registrada' }}
        </div>
    </div>

    <!-- Pie con datos del empleado y firma -->
    <div class="footer">
        <div class="employee-data">
            <div class="employee-title">Datos del Empleado Involucrado</div>
            <div class="employee-info">
                <div class="employee-row">
                    <div class="employee-cell employee-label">Nombre Completo:</div>
                    <div class="employee-cell employee-value">{{ $nonConformity->employee->lastName }} {{ $nonConformity->employee->name }}</div>
                </div>
                @if($nonConformity->employee->dni)
                <div class="employee-row">
                    <div class="employee-cell employee-label">DNI:</div>
                    <div class="employee-cell employee-value">{{ $nonConformity->employee->dni }}</div>
                </div>
                @endif
                @if($nonConformity->employee->currentJob)
                <div class="employee-row">
                    <div class="employee-cell employee-label">Puesto:</div>
                    <div class="employee-cell employee-value">{{ $nonConformity->employee->currentJob->name ?? 'N/A' }}</div>
                </div>
                @endif
                @if($nonConformity->employee->entry_date)
                <div class="employee-row">
                    <div class="employee-cell employee-label">Fecha de Ingreso:</div>
                    <div class="employee-cell employee-value">{{ \Carbon\Carbon::parse($nonConformity->employee->entry_date)->format('d/m/Y') }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-name">{{ $nonConformity->employee->lastName }} {{ $nonConformity->employee->name }}</div>
                    <div class="signature-label">Firma del Empleado</div>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-name">{{ $nonConformity->reporter->name ?? '' }}</div>
                    <div class="signature-label">Firma del Responsable</div>
                </div>
            </div>
        </div>
    </div>

    </div><!-- Cierre de content -->

    <!-- Footer de página -->
    <div class="page-footer">
        {{ $nonConformity->code }} | Generado: {{ now()->format('d/m/Y H:i') }} | IPAC - Sistema de Gestión de Calidad
    </div>
</body>
</html>
