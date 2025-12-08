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
            font-size: 10px;
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
            border-bottom: 3px solid #00a0b0;
            padding-bottom: 10px;
        }
        
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .logo-img {
            max-height: 50px;
            max-width: 120px;
        }
        
        /* Sample Info Bar */
        .sample-info-bar {
            background-color: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 14px;
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
            font-size: 11px;
            font-weight: bold;
            color: #00a0b0;
            margin: 15px 0 10px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #00a0b0;
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
            border-left: 2px solid #00a0b0;
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
            color: #00a0b0;
            font-size: 10px;
            text-transform: uppercase;
            vertical-align: top;
        }
        
        .det-name.is-child {
            font-size: 9px;
            text-transform: none;
        }
        
        .det-name.is-parent {
            font-size: 11px;
            color: #006070;
        }
        
        .det-reference-col {
            display: table-cell;
            width: 45%;
            text-align: right;
            font-size: 9px;
            color: #666;
            vertical-align: top;
        }
        
        .det-data-row {
            display: table;
            width: 100%;
            font-size: 9px;
            padding-left: 10px;
        }
        
        .det-label {
            display: table-cell;
            width: 70px;
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
        
        /* Observations */
        .observations-block {
            margin: 15px 0;
            padding: 10px 15px;
            background-color: #fffde7;
            border-left: 3px solid #ffc107;
            font-size: 9px;
        }
        
        .observations-title {
            font-weight: bold;
            color: #00a0b0;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        /* Footer / Validation */
        .validation-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
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
            font-size: 9px;
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
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }
        
        .validator-title {
            font-size: 9px;
            color: #666;
        }
        
        
        /* Page Footer */
        .page-footer {
            position: fixed;
            bottom: 15px;
            left: 30px;
            right: 30px;
            font-size: 7px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    @php
        // Filtrar solo determinaciones validadas y ordenar: padres primero, luego hijos
        $validatedDeterminations = $sample->determinations->where('is_validated', true);
        
        $orderedDeterminations = collect();
        $processed = [];
        
        foreach ($validatedDeterminations as $det) {
            if (in_array($det->id, $processed)) continue;
            
            if (!$det->test->parent) {
                $hasValidatedChildren = $validatedDeterminations->where('test.parent', $det->test_id)->count() > 0;
                $orderedDeterminations->push(['det' => $det, 'isChild' => false, 'isParent' => $hasValidatedChildren]);
                $processed[] = $det->id;
                
                foreach ($validatedDeterminations as $child) {
                    if ($child->test->parent == $det->test_id && !in_array($child->id, $processed)) {
                        $orderedDeterminations->push(['det' => $child, 'isChild' => true, 'isParent' => false]);
                        $processed[] = $child->id;
                    }
                }
            }
        }
        
        foreach ($validatedDeterminations as $det) {
            if (!in_array($det->id, $processed)) {
                $isChild = $det->test->parent ? true : false;
                $orderedDeterminations->push(['det' => $det, 'isChild' => $isChild, 'isParent' => false]);
            }
        }
    @endphp

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">IPAC Laboratorio de Aguas y Alimentos</div>
            </div>
            <div class="header-right">
                <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC" class="logo-img">
            </div>
        </div>
        
        <!-- Sample Info -->
        <div class="sample-info-bar">
            <div class="sample-info-row" style="display: table; width: 100%;">
                <span style="display: table-cell; text-align: left;">
                    <strong>Empresa:</strong> {{ strtoupper($sample->customer->name ?? 'N/A') }}
                </span>
                <span style="display: table-cell; text-align: right;">
                    <strong>{{ $sample->sampling_date?->format('d/m/Y') }} - {{ substr($sample->protocol_number, -2) }}</strong>
                </span>
            </div>
            <div class="sample-info-row" style="margin-top: 8px;">
                <strong style="font-size: 14px;">MUESTRA: {{ strtoupper($sample->sample_type) }} - {{ strtoupper($sample->location) }}</strong>
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
                
                // Para padres, obtener TODAS las categorías únicas de los hijos
                $parentCategories = null;
                if ($isParent) {
                    $categoryNames = collect();
                    
                    // Buscar categorías de todos los hijos validados
                    $childDeterminations = $validatedDeterminations->where('test.parent', $det->test_id);
                    
                    foreach ($childDeterminations as $child) {
                        // Buscar el TestReferenceValue con categoría para este hijo
                        $refValue = \App\Models\TestReferenceValue::where('test_id', $child->test_id)
                            ->whereNotNull('reference_category_id')
                            ->with('category')
                            ->first();
                        
                        if ($refValue && $refValue->category) {
                            $categoryNames->push($refValue->category->name);
                        }
                    }
                    
                    // Obtener categorías únicas
                    $uniqueCategories = $categoryNames->unique()->values();
                    
                    if ($uniqueCategories->count() > 0) {
                        $parentCategories = $uniqueCategories->implode(', ');
                    }
                }
            @endphp
            
            <div class="determination-item {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
                {{-- Header: Nombre y Referencia/Categoría --}}
                <div class="det-header-row">
                    <span class="det-name {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
                        {{ $det->test->name ?? 'N/A' }}
                    </span>
                    @if($isParent && $parentCategories)
                        <span class="det-reference-col" style="font-style: italic;">Valores de referencia según {{ $parentCategories }}</span>
                    @elseif(!$isParent && $det->reference_value)
                        <span class="det-reference-col">{{ $det->reference_value }}</span>
                    @endif
                </div>
                
                @if(!$isParent)
                    {{-- Resultado --}}
                    <div class="det-data-row">
                        <span class="det-label">Resultado:</span>
                        <span class="det-value result">{{ $det->result ?? '-' }}@if($det->unit) {{ $det->unit }}@endif</span>
                    </div>
                @endif
                
                {{-- Método (solo para padres o si no es hijo) --}}
                @if($det->method && ($isParent || !$isChild))
                <div class="det-data-row">
                    <span class="det-label">Método:</span>
                    <span class="det-value">{{ $det->method }}</span>
                </div>
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
