<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Circular {{ $circular->code }}</title>
    <style>
        @page {
            margin: 60px 50px 80px 50px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Header con tabla */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: middle;
            padding: 5px;
        }

        .logo-img {
            height: 55px;
        }

        .header-title {
            text-align: center;
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 2px;
        }

        .header-title p {
            font-size: 10px;
            color: #666;
            margin: 5px 0 0 0;
        }

        .code-box {
            text-align: right;
        }

        .code-box-inner {
            border: 2px solid #333;
            padding: 8px 12px;
            display: inline-block;
            text-align: center;
        }

        .code-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
        }

        .code-value {
            font-size: 13px;
            font-weight: bold;
        }

        /* Datos de la circular */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ccc;
        }

        .info-table td {
            padding: 8px 12px;
            border: 1px solid #ccc;
            font-size: 11px;
        }

        .info-label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 25%;
        }

        /* Título de la circular */
        .circular-title {
            background-color: #e8f4f8;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
        }

        .circular-title h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        /* Contenido */
        .content-section {
            margin-bottom: 25px;
        }

        .content-section h3 {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            color: #444;
        }

        .content-text {
            text-align: justify;
            line-height: 1.7;
            white-space: pre-line;
        }

        /* Tabla de firmas */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-section h3 {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .signature-intro {
            font-size: 10px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table th {
            background-color: #eee;
            border: 1px solid #333;
            padding: 8px 5px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .signature-table td {
            border: 1px solid #333;
            padding: 6px 5px;
            height: 35px;
            text-align: center;
            font-size: 10px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                <img src="{{ public_path('images/logo_ipac.png') }}" class="logo-img" alt="IPAC">
            </td>
            <td style="width: 50%;" class="header-title">
                <h1>CIRCULAR INTERNA</h1>
                <p>Sistema de Gestión de Calidad</p>
            </td>
            <td style="width: 25%;" class="code-box">
                <div class="code-box-inner">
                    <div class="code-label">Código</div>
                    <div class="code-value">{{ $circular->code }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Información -->
    <table class="info-table">
        <tr>
            <td class="info-label">Fecha:</td>
            <td>{{ $circular->date->format('d/m/Y') }}</td>
            <td class="info-label">Sector:</td>
            <td>{{ \App\Models\Circular::sectors()[$circular->sector] ?? $circular->sector }}</td>
        </tr>
        <tr>
            <td class="info-label">Emitido por:</td>
            <td>{{ $circular->creator->name }}</td>
            <td class="info-label">Estado:</td>
            <td>{{ ucfirst($circular->status) }}</td>
        </tr>
    </table>

    <!-- Título -->
    <div class="circular-title">
        <h2>{{ $circular->title }}</h2>
    </div>

    <!-- Contenido -->
    <div class="content-section">
        <h3>Contenido</h3>
        <div class="content-text">{{ $circular->description }}</div>
    </div>

    <!-- Firmas -->
    <div class="signature-section">
        <h3>Constancia de Lectura</h3>
        <p class="signature-intro">
            Por medio de la presente, declaro haber leído y comprendido el contenido de esta circular,
            comprometiéndome a cumplir con las disposiciones indicadas.
        </p>
        
        <table class="signature-table">
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 30%;">Nombre y Apellido</th>
                <th style="width: 15%;">Legajo</th>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 35%;">Firma</th>
            </tr>
            @for($i = 1; $i <= 8; $i++)
            <tr>
                <td>{{ $i }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ $circular->code }} | Generado: {{ now()->format('d/m/Y H:i') }} | IPAC - Sistema de Gestión de Calidad
    </div>
</body>
</html>
