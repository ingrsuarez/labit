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
        .det-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .det-table td {
            border: none;
            vertical-align: top;
        }

        .det-section-header td {
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
            background-color: #f0f0f0;
            border-bottom: 2px solid #333;
            padding: 6px;
        }

        .det-col-header td {
            font-weight: bold;
            font-size: 9pt;
            border-bottom: 1px solid #ccc;
            padding: 4px;
            color: #555;
        }

        .det-parent td {
            font-weight: bold;
            font-size: 10pt;
            padding: 6px 4px 2px 4px;
            color: #006070;
        }

        .det-subparent td {
            font-weight: bold;
            font-size: 9.5pt;
            padding: 5px 4px 2px 4px;
            color: #222;
        }

        .det-method td {
            font-size: 8pt;
            color: #666;
            font-style: italic;
            padding: 0 4px 4px 12px;
        }

        .det-child td {
            font-size: 9pt;
            padding: 3px 4px;
            border-bottom: 1px solid #eee;
        }

        .det-child td:first-child {
            padding-left: 20px;
        }

        .det-standalone td {
            font-size: 9pt;
            padding: 3px 4px;
            border-bottom: 1px solid #eee;
        }

        .det-result {
            font-weight: bold;
            color: #000;
            text-align: center;
        }

        .det-unit {
            text-align: center;
        }

        .det-ref {
            color: #666;
            text-align: right;
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
        $validatedDeterminations = $sample->determinations->where('is_validated', true);
        $allProtocolTestIds = $sample->determinations->pluck('test_id')->toArray();

        $itemsByTestId = [];
        foreach ($sample->determinations as $det) {
            $itemsByTestId[$det->test_id] = $det;
        }

        $parentMap = [];
        $childOf = [];
        $isSubParentMap = [];

        foreach ($validatedDeterminations as $det) {
            if (!$det->test) continue;
            $parentIds = $det->test->parentTests->pluck('id')->toArray();
            if ($det->test->parent) {
                $parentIds[] = $det->test->parent;
                $parentIds = array_unique($parentIds);
            }
            $parentsInProtocol = array_intersect($parentIds, $allProtocolTestIds);

            if (count($parentsInProtocol) > 0) {
                $parentId = reset($parentsInProtocol);
                $childOf[$det->test_id] = $parentId;
                if (!isset($parentMap[$parentId])) {
                    $parentMap[$parentId] = [];
                }
                $parentMap[$parentId][] = $det->test_id;
            }
        }

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach (array_keys($parentMap) as $pId) {
                if (isset($childOf[$pId])) continue;
                if (!isset($itemsByTestId[$pId])) continue;
                $pTest = $itemsByTestId[$pId]->test;
                if (!$pTest) continue;
                $ancestorIds = $pTest->parentTests->pluck('id')->toArray();
                if ($pTest->parent) {
                    $ancestorIds[] = $pTest->parent;
                    $ancestorIds = array_unique($ancestorIds);
                }
                $ancestorsInProtocol = array_intersect($ancestorIds, $allProtocolTestIds);
                if (count($ancestorsInProtocol) > 0) {
                    $ancestorId = reset($ancestorsInProtocol);
                    $childOf[$pId] = $ancestorId;
                    if (!isset($parentMap[$ancestorId])) {
                        $parentMap[$ancestorId] = [];
                    }
                    if (!in_array($pId, $parentMap[$ancestorId])) {
                        $parentMap[$ancestorId][] = $pId;
                    }
                    $changed = true;
                }
            }
        }

        foreach ($parentMap as $testId => $children) {
            if (isset($childOf[$testId])) {
                $isSubParentMap[$testId] = true;
            }
        }

        foreach ($parentMap as $parentId => &$pChildren) {
            usort($pChildren, function ($a, $b) use ($itemsByTestId) {
                $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
                $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;
                return $sortA <=> $sortB;
            });
        }
        unset($pChildren);

        $roots = [];
        foreach ($validatedDeterminations as $det) {
            if (!isset($childOf[$det->test_id])) {
                $roots[] = $det->test_id;
            }
        }
        foreach ($parentMap as $parentId => $children) {
            if (!isset($childOf[$parentId]) && !in_array($parentId, $roots)) {
                $roots[] = $parentId;
            }
        }

        usort($roots, function ($a, $b) use ($itemsByTestId) {
            $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
            $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;
            return $sortA <=> $sortB;
        });

        $orderedDeterminations = collect();

        $addWithChildren = function ($testId, $level) use (
            &$addWithChildren, $parentMap, $isSubParentMap, $itemsByTestId, &$orderedDeterminations
        ) {
            if (!isset($itemsByTestId[$testId])) return;

            $isParent = isset($parentMap[$testId]);
            $isSub = isset($isSubParentMap[$testId]);

            $orderedDeterminations->push([
                'det' => $itemsByTestId[$testId],
                'level' => $level,
                'isParent' => $isParent,
                'isSubParent' => $isSub,
                'isChild' => $level > 0,
            ]);

            if ($isParent) {
                foreach ($parentMap[$testId] as $childId) {
                    $addWithChildren($childId, $level + 1);
                }
            }
        };

        foreach ($roots as $rootId) {
            $addWithChildren($rootId, 0);
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
    
    <!-- Determinations -->
    <table class="det-table" cellpadding="0" cellspacing="0">
        <!-- Section header -->
        <tr class="det-section-header">
            <td colspan="4">
                {{ strtoupper($sample->sample_type) }} - {{ strtoupper($sample->location) }}
                @php
                    $firstCategoryName = null;
                    foreach ($orderedDeterminations as $entry) {
                        $d = $entry['det'];
                        if ($d->reference_category_id && $d->referenceCategory) {
                            $firstCategoryName = $d->referenceCategory->name;
                            break;
                        }
                    }
                @endphp
                @if($firstCategoryName)
                    <span style="font-size: 8pt; font-weight: normal; font-style: italic; margin-left: 10px;">
                        Valores de referencia según {{ $firstCategoryName }}
                    </span>
                @endif
            </td>
        </tr>
        <!-- Column headers -->
        <tr class="det-col-header">
            <td width="40%">Análisis</td>
            <td width="18%" style="text-align: center;">Resultado</td>
            <td width="12%" style="text-align: center;">Unidad</td>
            <td width="30%" style="text-align: right;">Valores de ref.</td>
        </tr>

        @foreach($orderedDeterminations as $entry)
            @php
                $det = $entry['det'];
                $level = $entry['level'];
                $isParent = $entry['isParent'];
                $isChild = $entry['isChild'];
                $isSub = $entry['isSubParent'];
                $indent = $level * 20;
            @endphp

            @if($isParent && !$isSub)
                <tr class="det-parent">
                    <td colspan="4">
                        {{ strtoupper($det->test->name ?? 'N/A') }}
                        @php
                            $childIds = $parentMap[$det->test_id] ?? [];
                            $childCategory = null;
                            foreach ($childIds as $cid) {
                                $cd = $itemsByTestId[$cid] ?? null;
                                if ($cd && $cd->reference_category_id && $cd->referenceCategory) {
                                    $childCategory = $cd->referenceCategory;
                                    break;
                                }
                            }
                            $pCatName = null;
                            if ($childCategory) {
                                $pCatName = $childCategory->name;
                            } elseif ($det->test->default_reference_category_id && $det->test->defaultReferenceCategory) {
                                $pCatName = $det->test->defaultReferenceCategory->name;
                            }
                        @endphp
                        @if($pCatName)
                            <span style="font-size: 8pt; font-weight: normal; color: #666; font-style: italic; float: right;">
                                Valores según {{ $pCatName }}
                            </span>
                        @endif
                    </td>
                </tr>
                @if($det->method)
                    <tr class="det-method">
                        <td colspan="4">{{ $det->method }}</td>
                    </tr>
                @endif
            @elseif($isSub)
                <tr class="det-subparent">
                    <td colspan="4" style="padding-left: {{ $indent }}px;">
                        {{ strtoupper($det->test->name ?? 'N/A') }}
                    </td>
                </tr>
                @if($det->method)
                    <tr class="det-method">
                        <td colspan="4" style="padding-left: {{ $indent }}px;">{{ $det->method }}</td>
                    </tr>
                @endif
            @elseif($isChild)
                <tr class="det-child">
                    <td style="padding-left: {{ $indent }}px;">{{ ucfirst($det->test->name ?? 'N/A') }}</td>
                    <td class="det-result">{{ $det->result ?? '-' }}</td>
                    <td class="det-unit">{{ $det->unit ?? '' }}</td>
                    <td class="det-ref">{{ ($det->reference_value !== null && $det->reference_value !== '') ? $det->reference_value : ($det->test->other_reference ?? '') }}</td>
                </tr>
                @if($det->method)
                    <tr class="det-method">
                        <td colspan="4" style="padding-left: {{ $indent }}px;">{{ $det->method }}</td>
                    </tr>
                @endif
            @else
                <tr class="det-standalone">
                    <td>{{ ucfirst($det->test->name ?? 'N/A') }}</td>
                    <td class="det-result">{{ $det->result ?? '-' }}</td>
                    <td class="det-unit">{{ $det->unit ?? '' }}</td>
                    <td class="det-ref">{{ ($det->reference_value !== null && $det->reference_value !== '') ? $det->reference_value : ($det->test->other_reference ?? '') }}</td>
                </tr>
                @if($det->method)
                    <tr class="det-method">
                        <td colspan="4" style="padding-left: 12px;">{{ $det->method }}</td>
                    </tr>
                @endif
            @endif

            @if($det->observations)
                <tr>
                    <td colspan="4" style="font-size: 8pt; font-style: italic; color: #666; padding: 0 4px 4px {{ $indent + 12 }}px;">
                        {{ $det->observations }}
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
    
    <!-- Análisis de Cumplimiento -->
    @php
        $categoriesUsed = collect();
        $allCompliant = true;
        $nonCompliantItems = [];
        $analyzedCount = 0;
        
        $negativeKeywords = ['ausencia', 'ausente', 'negativo', 'no detectado', 'nd', 'no detectable', '<1', '< 1', '<0', '< 0', '0', 'cero', 'ninguno', 'sin desarrollo'];
        $positiveKeywords = ['presencia', 'presente', 'positivo', 'detectado', 'desarrollo'];
        $absenceReferenceKeywords = ['ausencia', 'ausente', 'negativo', 'no detectable', 'ausencia en', 'ausencia/100', 'ausencia/25', '0 ufc', '0ufc'];
        
        foreach ($orderedDeterminations as $entry) {
            $det = $entry['det'];
            $isParent = $entry['isParent'];
            
            if ($isParent) continue;
            
            // Usar la categoría guardada directamente en la determinación
            if ($det->reference_category_id && $det->referenceCategory) {
                if (!$categoriesUsed->contains('id', $det->referenceCategory->id)) {
                    $categoriesUsed->push($det->referenceCategory);
                }
            } else {
                // Fallback: buscar por valor de referencia en TestReferenceValue
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
            }

            $refValue = null;
            if ($det->reference_value && $det->test) {
                $refValue = \App\Models\TestReferenceValue::where('test_id', $det->test_id)
                    ->where('value', $det->reference_value)
                    ->first();
            }
            
            if ($det->result !== null && $det->result !== '' && $det->reference_value !== null && $det->reference_value !== '') {
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
                        @if($sample->validator && $sample->validator->signature_path && file_exists($sample->validator->signatureAbsolutePath))
                            <img src="{{ $sample->validator->signatureAbsolutePath }}"
                                 style="max-height: 60px; max-width: 200px; margin-bottom: 5px;">
                        @endif
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
