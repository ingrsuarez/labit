<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protocolo {{ $sample->protocol_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .page {
            padding: 20px 30px;
            position: relative;
        }
        
        /* Header con logo */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 1px solid #030303;
            padding-bottom: 10px;
        }
        
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
        }
        
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 70%;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #C3C3C3;
        }
        
        .logo-img {
            max-height: 70px;
            max-width: 170px;
        }
        
        /* Sample Info Bar */
        .sample-info-bar {
            background-color: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 17px;
            color: #000;
        }
        
        .sample-info-row {
            margin-bottom: 3px;
        }
        
        .sample-info-row strong {
            color: #000;
        }
        
        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color:rgb(18, 18, 15);
            margin: 15px 0 10px 0;
            text-transform: uppercase;
            border-bottom: 2px solidrgb(12, 15, 15);
            padding-bottom: 3px;
        }
        
        /* Determinations Table Style */
        .determination-item {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        
        .determination-item.is-child {
            margin-left: 25px;
            padding-left: 10px;
            /* border-left: 2px solid #00a0b0; */
        }
        
        .determination-item.is-parent {
            background-color: #f0f9fa;
            padding: 8px 10px;
            margin-bottom: 5px;
        }
        
        .det-header-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        
        .det-name {
            display: table-cell;
            width: 55%;
            font-weight: bold;
            color:rgb(20, 27, 28);
            font-size: 12px;
            text-transform: uppercase;
            vertical-align: top;
        }
        
        .det-name.is-child {
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .det-name.is-parent {
            font-size: 13px;
            color: #006070;
        }
        
        .det-reference-col {
            display: table-cell;
            width: 45%;
            text-align: right;
            font-size: 11px;
            color: #666;
            vertical-align: top;
        }
        
        .det-data-row {
            display: table;
            width: 100%;
            font-size: 11px;
            padding-left: 10px;
        }
        
        .det-label {
            display: table-cell;
            width: 80px;
            color: #666;
            padding: 1px 0;
        }
        
        .det-value {
            display: table-cell;
            color: #333;
            padding: 1px 0;
        }
        
        .det-value.result {
            font-weight: bold;
            color: #000;
        }

        /* Layout resultado + referencia en línea */
        .result-row {
            display: table;
            width: 100%;
            font-size: 11px;
            padding-left: 10px;
            margin-bottom: 2px;
        }
        
        .result-label {
            display: table-cell;
            width: 80px;
            color: #666;
            vertical-align: middle;
        }
        
        .result-value {
            display: table-cell;
            width: 180px;
            font-weight: bold;
            color: #000;
            vertical-align: middle;
        }
        
        .result-reference {
            display: table-cell;
            text-align: left;
            color: #666;
            font-size: 11px;
            vertical-align: middle;
        }
        
        /* Observations */
        .observations-block {
            margin: 15px 0;
            padding: 10px 15px;
            background-color: #fffde7;
            border-left: 3px solid #ffc107;
            font-size: 11px;
        }
        
        .observations-title {
            font-weight: bold;
            color: #00a0b0;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        /* Footer / Validation - Siempre al final de la página */
        .validation-section {
            position: fixed;
            bottom: 40px;
            left: 30px;
            right: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            background: #fff;
        }
        
        .validation-row {
            display: table;
            width: 100%;
        }
        
        .validation-left {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }
        
        .validation-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }
        
        .validation-text {
            font-size: 11px;
            color: #333;
            margin-bottom: 2px;
        }
        
        .validation-text strong {
            color: #000;
        }
        
        .signature-area {
            text-align: center;
            padding-top: 20px;
        }
        
        .signature-line {
            width: 150px;
            border-top: 1px solid #333;
            margin: 0 0 0 auto;
            padding-top: 5px;
        }
        
        .validator-name {
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }
        
        .validator-title {
            font-size: 11px;
            color: #666;
        }
        
        
        /* Page Footer - Siempre al final absoluto */
        .page-footer {
            position: fixed;
            bottom: 10px;
            left: 30px;
            right: 30px;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 5px;
            background: #fff;
        }
        
        /* Espacio reservado para el footer fijo */
        .content-area {
            padding-bottom: 120px;
        }
    </style>
</head>
<body>
    @php
        // Filtrar solo determinaciones validadas y ordenar: padres primero, luego hijos
        $validatedDeterminations = $sample->determinations->where('is_validated', true);
        
        // Función helper para verificar si un test es padre de otro
        $isParentOf = function($parentTestId, $childTestId) {
            $childTest = \App\Models\Test::find($childTestId);
            if (!$childTest) return false;
            
            // Verificar relación legacy
            if ($childTest->parent == $parentTestId) return true;
            
            // Verificar relación many-to-many
            return $childTest->parentTests()->where('parent_test_id', $parentTestId)->exists();
        };
        
        // Identificar qué tests son padres (tienen hijos validados en este protocolo)
        $parentTestIds = [];
        $childTestIds = [];
        
        foreach ($validatedDeterminations as $det) {
            $test = $det->test;
            
            // Obtener todos los hijos del test (legacy + pivot)
            $allChildren = $test->getAllChildren(false);
            
            foreach ($allChildren as $child) {
                // Si el hijo está validado en este protocolo
                if ($validatedDeterminations->where('test_id', $child->id)->count() > 0) {
                    $parentTestIds[$det->test_id] = true;
                    $childTestIds[$child->id] = true;
                }
            }
        }
        
        $orderedDeterminations = collect();
        $processed = [];
        
        // Primero procesar padres y sus hijos
        foreach ($validatedDeterminations as $det) {
            if (in_array($det->id, $processed)) continue;
            
            // Si es un padre
            if (isset($parentTestIds[$det->test_id])) {
                $orderedDeterminations->push(['det' => $det, 'isChild' => false, 'isParent' => true]);
                $processed[] = $det->id;
                
                // Agregar sus hijos validados
                foreach ($validatedDeterminations as $childDet) {
                    if (in_array($childDet->id, $processed)) continue;
                    
                    if ($isParentOf($det->test_id, $childDet->test_id)) {
                        $orderedDeterminations->push(['det' => $childDet, 'isChild' => true, 'isParent' => false]);
                        $processed[] = $childDet->id;
                    }
                }
            }
        }
        
        // Agregar determinaciones restantes (sin padre en este protocolo)
        foreach ($validatedDeterminations as $det) {
            if (!in_array($det->id, $processed)) {
                $isChild = isset($childTestIds[$det->test_id]);
                $orderedDeterminations->push(['det' => $det, 'isChild' => $isChild, 'isParent' => false]);
                $processed[] = $det->id;
            }
        }
    @endphp

    <div class="page content-area">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC" class="logo-img">
            </div>
            <div class="header-right">
                <div class="company-name">IPAC Laboratorio de Aguas y Alimentos</div>
            </div>
        </div>
        
        <!-- Sample Info -->
        <div class="sample-info-bar">
            <div class="sample-info-row" style="display: table; width: 100%;">
                <span style="display: table-cell; text-align: left;">
                    Empresa: <strong>{{ strtoupper($sample->customer->name ?? 'N/A') }} </strong>
                </span>
                <span style="display: table-cell; text-align: right;">
                    <strong>{{ $sample->sampling_date?->format('d/m/Y') }} - {{ substr($sample->protocol_number, -2) }}</strong>
                </span>
            </div>
            <div class="sample-info-row" style="margin-top: 8px;">
                Muestra: <strong style="font-size: 14px;">{{ strtoupper($sample->sample_type) }} - {{ strtoupper($sample->location) }}</strong>
            </div>
        </div>
        
        <!-- Section Title -->
        <div class="section-title">
            {{ strtoupper($sample->sample_type) }} - {{ strtoupper($sample->location) }}
        </div>
        
        <!-- Determinations -->
        @foreach($orderedDeterminations as $item)
            @php 
                $det = $item['det'];
                $isChild = $item['isChild'];
                $isParent = $item['isParent'];
                
                // Para padres, mostrar solo la categoría predeterminada configurada en el padre
                $parentCategories = null;
                if ($isParent && $det->test->default_reference_category_id && $det->test->defaultReferenceCategory) {
                    $parentCategories = $det->test->defaultReferenceCategory->name;
                }
            @endphp
            
            <div class="determination-item {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
                {{-- Header: Nombre del test --}}
                <div class="det-header-row">
                    <span class="det-name {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
                        {{ $det->test->name ?? 'N/A' }}
                    </span>
                    @if($isParent && $parentCategories)
                        <span class="det-reference-col" style="font-style: italic;">Valores de referencia según {{ $parentCategories }}</span>
                    @endif
                </div>
                
                @if(!$isParent)
                    {{-- Resultado + Valor de referencia en la misma línea --}}
                    <div class="result-row">
                        <span class="result-label">Resultado:</span>
                        <span class="result-value">{{ $det->result ?? '-' }}@if($det->unit) {{ $det->unit }}@endif</span>
                        @if($det->reference_value)
                            <span class="result-reference">{{ $det->reference_value }}</span>
                        @endif
                    </div>
                    
                    {{-- Método --}}
                    @if($det->method)
                    <div class="det-data-row">
                        <span class="det-label">Método:</span>
                        <span class="det-value">{{ $det->method }}</span>
                    </div>
                    @endif
                @endif
                
                {{-- Observaciones de la determinación --}}
                @if($det->observations)
                <div class="det-data-row" style="margin-top: 2px;">
                    <span class="det-label"></span>
                    <span class="det-value" style="font-style: italic; color: #666;">{{ $det->observations }}</span>
                </div>
                @endif
            </div>
        @endforeach
        
        <!-- General Observations -->
        @if($sample->observations)
        <div class="observations-block">
            <div class="observations-title">OBSERVACIONES</div>
            <p>{{ $sample->observations }}</p>
        </div>
        @endif
        
        <!-- Validation Footer -->
        <div class="validation-section">
            <div class="validation-row">
                <div class="validation-left">
                    <div class="validation-text"><strong>RESULTADO VALIDADO POR SISTEMA DIGITAL</strong></div>
                    <div class="validation-text">www.ipac.com.ar</div>
                    <br>
                    <div class="validation-text">Leguizamón 356. TEL: 0299-6227547 Neuquen</div>
                </div>
                <div class="validation-right">
                    <div class="signature-area">
                        <div class="signature-line">
                            <div class="validator-name">{{ strtoupper($sample->validator->name ?? 'DRA. CLARA SILVINA') }}</div>
                            <div class="validator-title">Director Técnico</div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Page Footer -->
        <div class="page-footer">
            Protocolo {{ $sample->protocol_number }} | Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
