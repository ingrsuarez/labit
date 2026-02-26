<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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
            font-size: 13px;
            font-weight: bold;
            color: #181812;
            margin: 15px 0 10px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #0c0f0f;
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
        }
        
        .determination-item.is-parent {
            background-color: #f0f9fa;
            padding: 8px 10px;
            margin-bottom: 5px;
        }
        
        .det-name {
            font-weight: bold;
            color: #141b1c;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .det-name.is-child {
            font-size: 11px;
        }
        
        .det-name.is-parent {
            font-size: 13px;
            color: #006070;
        }
        
        .det-reference {
            font-size: 11px;
            color: #666;
            float: right;
        }
        
        .det-data-row {
            font-size: 11px;
            padding-left: 10px;
            margin-top: 3px;
        }
        
        .det-label {
            color: #666;
            display: inline-block;
            width: 80px;
        }
        
        .det-value {
            color: #333;
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
        
        /* Validation Section */
        .validation-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            page-break-inside: avoid;
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
            text-align: right;
            margin-top: 20px;
        }
        
        .signature-line {
            width: 150px;
            border-top: 1px solid #333;
            margin-left: auto;
            padding-top: 5px;
            text-align: center;
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
    </style>
</head>
<body>
    {{-- HEADER que se repite en cada página --}}
    <htmlpageheader name="myHeader">
        <table width="100%" style="border-bottom: 2px solid #030303; padding-bottom: 10px;">
            <tr>
                <td width="30%" style="vertical-align: middle;">
                    <img src="{{ public_path('images/logo_ipac.png') }}" style="max-height: 60px; max-width: 150px;">
                </td>
                <td width="70%" style="vertical-align: middle; text-align: right;">
                    <span style="font-size: 20px; font-weight: bold; color: #C3C3C3;">IPAC Laboratorio de Aguas y Alimentos</span>
                </td>
            </tr>
        </table>
    </htmlpageheader>
    <sethtmlpageheader name="myHeader" value="on" show-this-page="1" />

    {{-- FOOTER que se repite en cada página --}}
    <htmlpagefooter name="myFooter">
        <div style="font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 5px;">
            Protocolo {{ $sample->protocol_number }} | Página {PAGENO} de {nbpg} | Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="myFooter" value="on" />

    @php
        // Filtrar solo determinaciones validadas y ordenar: padres primero, luego hijos
        $validatedDeterminations = $sample->determinations->where('is_validated', true);
        
        $isParentOf = function($parentTestId, $childTestId) {
            $childTest = \App\Models\Test::find($childTestId);
            if (!$childTest) return false;
            if ($childTest->parent == $parentTestId) return true;
            return $childTest->parentTests()->where('parent_test_id', $parentTestId)->exists();
        };
        
        $parentTestIds = [];
        $childTestIds = [];
        
        foreach ($validatedDeterminations as $det) {
            $test = $det->test;
            $allChildren = $test->getAllChildren(false);
            
            foreach ($allChildren as $child) {
                if ($validatedDeterminations->where('test_id', $child->id)->count() > 0) {
                    $parentTestIds[$det->test_id] = true;
                    $childTestIds[$child->id] = true;
                }
            }
        }
        
        $orderedDeterminations = collect();
        $processed = [];
        
        foreach ($validatedDeterminations as $det) {
            if (in_array($det->id, $processed)) continue;
            
            if (isset($parentTestIds[$det->test_id])) {
                $orderedDeterminations->push(['det' => $det, 'isChild' => false, 'isParent' => true]);
                $processed[] = $det->id;
                
                foreach ($validatedDeterminations as $childDet) {
                    if (in_array($childDet->id, $processed)) continue;
                    
                    if ($isParentOf($det->test_id, $childDet->test_id)) {
                        $orderedDeterminations->push(['det' => $childDet, 'isChild' => true, 'isParent' => false]);
                        $processed[] = $childDet->id;
                    }
                }
            }
        }
        
        foreach ($validatedDeterminations as $det) {
            if (!in_array($det->id, $processed)) {
                $isChild = isset($childTestIds[$det->test_id]);
                $orderedDeterminations->push(['det' => $det, 'isChild' => $isChild, 'isParent' => false]);
                $processed[] = $det->id;
            }
        }
    @endphp

    <!-- Sample Info -->
    <div class="sample-info-bar">
        <table width="100%">
            <tr>
                <td>Empresa: <strong>{{ strtoupper($sample->customer->name ?? 'N/A') }}</strong></td>
                <td style="text-align: right;"><strong>{{ $sample->sampling_date?->format('d/m/Y') }} - {{ substr($sample->protocol_number, -2) }}</strong></td>
            </tr>
        </table>
        @if($sample->address)
        <div style="margin-top: 4px;">
            Dirección: <strong>{{ $sample->address }}</strong>
        </div>
        @endif
        <div style="margin-top: 8px;">
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
            
            $parentCategories = null;
            if ($isParent && $det->test->default_reference_category_id && $det->test->defaultReferenceCategory) {
                $parentCategories = $det->test->defaultReferenceCategory->name;
            }
        @endphp
        
        <div class="determination-item {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
            <div>
                <span class="det-name {{ $isChild ? 'is-child' : '' }} {{ $isParent ? 'is-parent' : '' }}">
                    {{ $det->test->name ?? 'N/A' }}
                </span>
                @if($isParent && $parentCategories)
                    <span class="det-reference">Valores de referencia según {{ $parentCategories }}</span>
                @endif
            </div>
            
            @if(!$isParent)
                <div class="det-data-row">
                    <span class="det-label">Resultado:</span>
                    <span class="det-value result">{{ $det->result ?? '-' }}@if($det->unit) {{ $det->unit }}@endif</span>
                    @if($det->reference_value)
                        <span style="margin-left: 20px; color: #666;">{{ $det->reference_value }}</span>
                    @endif
                </div>
                
                @if($det->method)
                <div class="det-data-row">
                    <span class="det-label">Método:</span>
                    <span class="det-value">{{ $det->method }}</span>
                </div>
                @endif
            @endif
            
            @if($det->observations)
            <div class="det-data-row">
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
        
        $negativeKeywords = ['ausencia', 'ausente', 'negativo', 'no detectado', 'nd', 'no detectable', '<1', '< 1', '<0', '< 0', '0', 'cero', 'ninguno', 'sin desarrollo'];
        $positiveKeywords = ['presencia', 'presente', 'positivo', 'detectado', 'desarrollo'];
        $absenceReferenceKeywords = ['ausencia', 'ausente', 'negativo', 'no detectable', 'ausencia en', 'ausencia/100', 'ausencia/25', '0 ufc', '0ufc'];
        
        foreach ($orderedDeterminations as $item) {
            $det = $item['det'];
            $isParent = $item['isParent'];
            
            if ($isParent) continue;
            
            $refValue = null;
            if ($det->reference_value && $det->test) {
                $refValue = \App\Models\TestReferenceValue::where('test_id', $det->test_id)
                    ->where('value', $det->reference_value)
                    ->first();
            }
            
            if ($refValue && $refValue->category) {
                if (!$categoriesUsed->contains('id', $refValue->category->id)) {
                    $categoriesUsed->push($refValue->category);
                }
            }
            
            if ($det->result !== null && $det->result !== '' && $det->reference_value) {
                $analyzedCount++;
                
                $resultLower = strtolower(trim($det->result));
                $refLower = strtolower(trim($det->reference_value));
                
                $numericResult = null;
                $cleanResult = str_replace([',', ' '], ['.', ''], $det->result);
                if (preg_match('/^<?(\d+\.?\d*)/', $cleanResult, $matches)) {
                    $numericResult = floatval($matches[1]);
                    if (strpos($cleanResult, '<') === 0 && $numericResult <= 1) {
                        $numericResult = 0;
                    }
                }
                
                $resultIsNegative = false;
                foreach ($negativeKeywords as $keyword) {
                    if (strpos($resultLower, $keyword) !== false) {
                        $resultIsNegative = true;
                        break;
                    }
                }
                if ($numericResult === 0.0 || $numericResult === 0) {
                    $resultIsNegative = true;
                }
                
                $resultIsPositive = false;
                foreach ($positiveKeywords as $keyword) {
                    if (strpos($resultLower, $keyword) !== false) {
                        $resultIsPositive = true;
                        break;
                    }
                }
                
                $refRequiresAbsence = false;
                foreach ($absenceReferenceKeywords as $keyword) {
                    if (strpos($refLower, $keyword) !== false) {
                        $refRequiresAbsence = true;
                        break;
                    }
                }
                
                if ($refRequiresAbsence) {
                    if ($resultIsPositive) {
                        $allCompliant = false;
                        $nonCompliantItems[] = $det->test->name;
                    } elseif (!$resultIsNegative && $numericResult !== null && $numericResult > 0) {
                        $allCompliant = false;
                        $nonCompliantItems[] = $det->test->name;
                    }
                } elseif ($refValue && ($refValue->min_value !== null || $refValue->max_value !== null)) {
                    if ($numericResult !== null) {
                        $minVal = $refValue->min_value !== null ? floatval($refValue->min_value) : null;
                        $maxVal = $refValue->max_value !== null ? floatval($refValue->max_value) : null;
                        
                        $complies = true;
                        if ($minVal !== null && $numericResult < $minVal) $complies = false;
                        if ($maxVal !== null && $numericResult > $maxVal) $complies = false;
                        
                        if (!$complies) {
                            $allCompliant = false;
                            $nonCompliantItems[] = $det->test->name;
                        }
                    }
                } elseif (preg_match('/^[<≤]\s*(\d+\.?\d*)/', $refLower, $refMatches)) {
                    $maxFromRef = floatval($refMatches[1]);
                    if ($numericResult !== null && $numericResult > $maxFromRef) {
                        $allCompliant = false;
                        $nonCompliantItems[] = $det->test->name;
                    }
                } elseif (preg_match('/^(\d+\.?\d*)\s*[-–]\s*(\d+\.?\d*)/', str_replace(',', '.', $det->reference_value), $refMatches)) {
                    $minFromRef = floatval($refMatches[1]);
                    $maxFromRef = floatval($refMatches[2]);
                    if ($numericResult !== null) {
                        if ($numericResult < $minFromRef || $numericResult > $maxFromRef) {
                            $allCompliant = false;
                            $nonCompliantItems[] = $det->test->name;
                        }
                    }
                }
            }
        }
        
        foreach ($orderedDeterminations as $item) {
            if ($item['isParent'] && $item['det']->test && $item['det']->test->defaultReferenceCategory) {
                $cat = $item['det']->test->defaultReferenceCategory;
                if (!$categoriesUsed->contains('id', $cat->id)) {
                    $categoriesUsed->push($cat);
                }
            }
        }
    @endphp
    
    <!-- Conclusión -->
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
            </div>
        @endif
    </div>

    <!-- Observations -->
    @if($sample->observations)
    <div class="observations-block">
        <div class="observations-title">OBSERVACIONES</div>
        <p>{{ $sample->observations }}</p>
    </div>
    @endif
    
    <!-- Validation Section -->
    <div class="validation-section">
        <table width="100%">
            <tr>
                <td width="50%" style="vertical-align: bottom;">
                    <div class="validation-text"><strong>RESULTADO VALIDADO POR SISTEMA DIGITAL</strong></div>
                    <div class="validation-text">www.ipac.com.ar</div>
                    <br>
                    <div class="validation-text">Leguizamón 356. TEL: 0299-6227547 Neuquen</div>
                </td>
                <td width="50%" style="vertical-align: bottom; text-align: right;">
                    <div class="signature-area">
                        <div class="signature-line">
                            <div class="validator-name">{{ strtoupper($sample->validator->name ?? 'DRA. CLARA SILVINA') }}</div>
                            <div class="validator-title">Director Técnico</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
