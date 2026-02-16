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
        
        @page {
            margin: 20px 30px;
        }
        
        .page {
            padding: 0;
            position: relative;
        }
        
        /* Header con logo - FIJO EN CADA PÁGINA */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 2px solid #030303;
            padding-bottom: 10px;
            background: #fff;
        }
        
        .header-table {
            display: table;
            width: 100%;
            height: 70px;
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
            max-height: 60px;
            max-width: 150px;
        }
        
        /* Espacio para el header fijo */
        .header-spacer {
            height: 90px;
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
        
        /* Conclusion Block */
        .conclusion-block {
            margin: 20px 0;
            padding: 12px 15px;
            border: 2px solid #333;
            font-size: 11px;
            page-break-inside: avoid;
        }
        
        .conclusion-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .conclusion-text {
            margin-bottom: 5px;
            line-height: 1.5;
        }
        
        .conclusion-result {
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
            padding: 8px;
            text-align: center;
        }
        
        .conclusion-result.cumple {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        
        .conclusion-result.no-cumple {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }
        
        .categories-list {
            margin-top: 5px;
            padding-left: 15px;
            color: #555;
        }
        
        /* Footer / Validation - Siempre al final de la última página */
        .validation-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            background: #fff;
            page-break-inside: avoid;
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
        
        
        /* Page Footer - Fijo en cada página */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding: 5px 0;
            background: #fff;
        }
        
        /* Área de contenido */
        .content-area {
            padding-bottom: 30px;
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
        <!-- Header - Fijo en cada página -->
        <div class="header">
            <div class="header-table">
                <div class="header-left">
                    <img src="{{ public_path('images/logo_ipac.png') }}" alt="IPAC" class="logo-img">
                </div>
                <div class="header-right">
                    <div class="company-name">IPAC Laboratorio de Aguas y Alimentos</div>
                </div>
            </div>
        </div>
        
        <!-- Espaciador para el header fijo -->
        <div class="header-spacer"></div>
        
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
            @if($sample->address)
            <div class="sample-info-row" style="margin-top: 4px;">
                Dirección: <strong>{{ $sample->address }}</strong>
            </div>
            @endif
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
        
        <!-- Análisis de Cumplimiento -->
        @php
            $categoriesUsed = collect();
            $allCompliant = true;
            $nonCompliantItems = [];
            $analyzedCount = 0;
            
            // Palabras clave para detectar resultados negativos (ausencia)
            $negativeKeywords = ['ausencia', 'ausente', 'negativo', 'no detectado', 'nd', 'no detectable', 
                                 '<1', '< 1', '<0', '< 0', '0', 'cero', 'ninguno', 'sin desarrollo'];
            
            // Palabras clave para detectar resultados positivos (presencia)
            $positiveKeywords = ['presencia', 'presente', 'positivo', 'detectado', 'desarrollo'];
            
            // Palabras clave en el valor de referencia que indican que debe ser ausente
            $absenceReferenceKeywords = ['ausencia', 'ausente', 'negativo', 'no detectable', 
                                          'ausencia en', 'ausencia/100', 'ausencia/25', '0 ufc', '0ufc'];
            
            foreach ($orderedDeterminations as $item) {
                $det = $item['det'];
                $isParent = $item['isParent'];
                
                // Solo analizar determinaciones que no son padres (tienen resultados)
                if ($isParent) continue;
                
                // Buscar el valor de referencia en la base de datos
                $refValue = null;
                if ($det->reference_value && $det->test) {
                    $refValue = \App\Models\TestReferenceValue::where('test_id', $det->test_id)
                        ->where('value', $det->reference_value)
                        ->first();
                    
                    // Si no encontró por valor exacto, buscar por categoría del padre
                    if (!$refValue && $det->test->parentTests && $det->test->parentTests->count() > 0) {
                        $parentTest = $det->test->parentTests->first();
                        if ($parentTest && $parentTest->default_reference_category_id) {
                            $refValue = \App\Models\TestReferenceValue::where('test_id', $det->test_id)
                                ->where('reference_category_id', $parentTest->default_reference_category_id)
                                ->first();
                        }
                    }
                }
                
                // Registrar categoría usada
                if ($refValue && $refValue->category) {
                    if (!$categoriesUsed->contains('id', $refValue->category->id)) {
                        $categoriesUsed->push($refValue->category);
                    }
                }
                
                // Analizar cumplimiento solo si hay resultado y valor de referencia
                if ($det->result !== null && $det->result !== '' && $det->reference_value) {
                    $analyzedCount++;
                    
                    // Normalizar textos para comparación
                    $resultLower = strtolower(trim($det->result));
                    $refLower = strtolower(trim($det->reference_value));
                    
                    // Extraer valor numérico del resultado si existe
                    $numericResult = null;
                    $cleanResult = str_replace([',', ' '], ['.', ''], $det->result);
                    if (preg_match('/^<?(\d+\.?\d*)/', $cleanResult, $matches)) {
                        $numericResult = floatval($matches[1]);
                        // Si empieza con < y el número es pequeño, tratarlo como cero efectivo
                        if (strpos($cleanResult, '<') === 0 && $numericResult <= 1) {
                            $numericResult = 0;
                        }
                    }
                    
                    // Verificar si el resultado indica negativo/ausencia
                    $resultIsNegative = false;
                    foreach ($negativeKeywords as $keyword) {
                        if (strpos($resultLower, $keyword) !== false) {
                            $resultIsNegative = true;
                            break;
                        }
                    }
                    // También es negativo si el número es 0
                    if ($numericResult === 0.0 || $numericResult === 0) {
                        $resultIsNegative = true;
                    }
                    
                    // Verificar si el resultado indica positivo/presencia
                    $resultIsPositive = false;
                    foreach ($positiveKeywords as $keyword) {
                        if (strpos($resultLower, $keyword) !== false) {
                            $resultIsPositive = true;
                            break;
                        }
                    }
                    
                    // Verificar si el valor de referencia requiere ausencia
                    $refRequiresAbsence = false;
                    foreach ($absenceReferenceKeywords as $keyword) {
                        if (strpos($refLower, $keyword) !== false) {
                            $refRequiresAbsence = true;
                            break;
                        }
                    }
                    
                    // CASO 1: El valor de referencia requiere AUSENCIA (ej: coliformes, E.coli)
                    if ($refRequiresAbsence) {
                        // Cumple si el resultado es negativo/ausente
                        // No cumple si el resultado es positivo o tiene valor numérico > 0
                        if ($resultIsPositive) {
                            $allCompliant = false;
                            $nonCompliantItems[] = $det->test->name;
                        } elseif (!$resultIsNegative && $numericResult !== null && $numericResult > 0) {
                            $allCompliant = false;
                            $nonCompliantItems[] = $det->test->name;
                        }
                    }
                    // CASO 2: El valor de referencia tiene límites numéricos en la BD
                    elseif ($refValue && ($refValue->min_value !== null || $refValue->max_value !== null)) {
                        if ($numericResult !== null) {
                            $minVal = $refValue->min_value !== null ? floatval($refValue->min_value) : null;
                            $maxVal = $refValue->max_value !== null ? floatval($refValue->max_value) : null;
                            
                            $complies = true;
                            if ($minVal !== null && $numericResult < $minVal) {
                                $complies = false;
                            }
                            if ($maxVal !== null && $numericResult > $maxVal) {
                                $complies = false;
                            }
                            
                            if (!$complies) {
                                $allCompliant = false;
                                $nonCompliantItems[] = $det->test->name;
                            }
                        }
                    }
                    // CASO 3: El valor de referencia tiene formato "< X" o "<= X" (máximo implícito)
                    elseif (preg_match('/^[<≤]\s*(\d+\.?\d*)/', $refLower, $refMatches)) {
                        $maxFromRef = floatval($refMatches[1]);
                        if ($numericResult !== null && $numericResult > $maxFromRef) {
                            $allCompliant = false;
                            $nonCompliantItems[] = $det->test->name;
                        }
                    }
                    // CASO 4: El valor de referencia tiene formato "X - Y" (rango implícito)
                    elseif (preg_match('/^(\d+\.?\d*)\s*[-–]\s*(\d+\.?\d*)/', str_replace(',', '.', $det->reference_value), $refMatches)) {
                        $minFromRef = floatval($refMatches[1]);
                        $maxFromRef = floatval($refMatches[2]);
                        if ($numericResult !== null) {
                            if ($numericResult < $minFromRef || $numericResult > $maxFromRef) {
                                $allCompliant = false;
                                $nonCompliantItems[] = $det->test->name;
                            }
                        }
                    }
                    // CASO 5: Comparación directa de textos (ej: resultado="Ausente", ref="Ausente")
                    elseif ($resultLower === $refLower) {
                        // Coinciden exactamente, cumple
                    }
                }
            }
            
            // Agregar categorías de determinaciones padre
            foreach ($orderedDeterminations as $item) {
                if ($item['isParent'] && $item['det']->test && $item['det']->test->defaultReferenceCategory) {
                    $cat = $item['det']->test->defaultReferenceCategory;
                    if (!$categoriesUsed->contains('id', $cat->id)) {
                        $categoriesUsed->push($cat);
                    }
                }
            }
        @endphp
        
        <!-- Conclusión / Dictamen -->
        <div class="conclusion-block">
            <div class="conclusion-title">CONCLUSIÓN</div>
            
            @if($categoriesUsed->count() > 0)
                <div class="conclusion-text">
                    <strong>Valores de referencia según:</strong>
                    <div class="categories-list">
                        @foreach($categoriesUsed->sortBy('name') as $category)
                            • {{ $category->name }} @if($category->code)({{ $category->code }})@endif<br>
                        @endforeach
                    </div>
                </div>
            @endif
            
            @if($analyzedCount > 0)
                <div class="conclusion-result {{ $allCompliant ? 'cumple' : 'no-cumple' }}">
                    @if($allCompliant)
                        LA MUESTRA ANALIZADA CUMPLE CON LOS VALORES DE REFERENCIA ESTABLECIDOS
                    @else
                        LA MUESTRA ANALIZADA NO CUMPLE CON LOS VALORES DE REFERENCIA ESTABLECIDOS
                        @if(count($nonCompliantItems) > 0)
                            <br><span style="font-size: 10px; font-weight: normal;">
                                Parámetros fuera de especificación: {{ implode(', ', $nonCompliantItems) }}
                            </span>
                        @endif
                    @endif
                </div>
            @else
                <div class="conclusion-text" style="font-style: italic; color: #666;">
                    No se pudo determinar el cumplimiento automáticamente. 
                    Consulte los resultados individuales y valores de referencia.
                </div>
            @endif
        </div>

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
