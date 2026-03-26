<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Protocolo {{ $admission->protocol_number }}</title>
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
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #181812;
            margin: 15px 0 10px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #0c0f0f;
            padding-bottom: 3px;
        }
        
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
                    <span style="font-size: 20px; font-weight: bold; color: #C3C3C3;">IPAC Laboratorio de Análisis Clínicos</span>
                </td>
            </tr>
        </table>
    </htmlpageheader>
    <sethtmlpageheader name="myHeader" value="on" show-this-page="1" />

    {{-- FOOTER que se repite en cada página --}}
    <htmlpagefooter name="myFooter">
        <div style="font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 5px;">
            Protocolo {{ $admission->protocol_number }} | Página {PAGENO} de {nbpg} | Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="myFooter" value="on" />

    @php
        $validatedTests = $admission->admissionTests->where('is_validated', true);
        $allTests = $admission->admissionTests;

        $itemsByTestId = [];
        foreach ($validatedTests as $at) {
            $itemsByTestId[$at->test_id] = $at;
        }

        $allItemsByTestId = [];
        foreach ($allTests as $at) {
            $allItemsByTestId[$at->test_id] = $at;
        }

        $testIds = $validatedTests->pluck('test_id')->toArray();
        $allTestIds = $allTests->pluck('test_id')->toArray();
        $parentMap = [];
        $childOf = [];
        $isSubParentMap = [];

        foreach ($allTests as $at) {
            $test = $at->test;
            if (!$test) continue;

            $children = $test->childTests()
                ->whereIn('tests.id', $testIds)
                ->orderBy('test_parents.order')
                ->pluck('tests.id')
                ->toArray();

            if (!empty($children)) {
                $parentMap[$test->id] = $children;
                if (!isset($itemsByTestId[$test->id])) {
                    $itemsByTestId[$test->id] = $at;
                }
                foreach ($children as $childId) {
                    $childOf[$childId] = $test->id;
                }
            }
        }

        foreach ($parentMap as $testId => $children) {
            if (isset($childOf[$testId])) {
                $isSubParentMap[$testId] = true;
            }
        }

        $roots = [];
        foreach ($validatedTests as $at) {
            if (!isset($childOf[$at->test_id])) {
                $roots[] = $at->test_id;
            }
        }

        usort($roots, function ($a, $b) use ($itemsByTestId) {
            $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
            $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;
            return $sortA <=> $sortB;
        });

        $orderedTests = collect();

        $addWithChildren = function ($testId, $level) use (
            &$addWithChildren, $parentMap, $isSubParentMap, $itemsByTestId, &$orderedTests
        ) {
            if (!isset($itemsByTestId[$testId])) return;

            $isParent = isset($parentMap[$testId]);
            $isSub = isset($isSubParentMap[$testId]);

            $orderedTests->push([
                'at' => $itemsByTestId[$testId],
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

    <!-- Patient Info -->
    <div class="sample-info-bar">
        <table width="100%">
            <tr>
                <td>
                    Paciente: <strong>{{ strtoupper(($admission->patient->name ?? '') . ' ' . ($admission->patient->last_name ?? $admission->patient->lastName ?? '')) }}</strong>
                </td>
                <td style="text-align: right;">
                    Protocolo: <strong>{{ $admission->protocol_number }}</strong>
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 4px;">
            <tr>
                <td width="50%">DNI: <strong>{{ $admission->patient->patientId ?? $admission->patient->dni ?? 'N/A' }}</strong></td>
                <td width="50%" style="text-align: right;">Fecha: <strong>{{ $admission->date?->format('d/m/Y') }}</strong></td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 4px;">
            <tr>
                <td width="50%">Obra Social: <strong>{{ strtoupper($admission->insuranceRelation->name ?? 'N/A') }}</strong></td>
                <td width="50%" style="text-align: right;">Nro. Afiliado: <strong>{{ $admission->affiliate_number ?? 'N/A' }}</strong></td>
            </tr>
        </table>
        @if($admission->requesting_doctor)
        <div style="margin-top: 4px;">
            Médico Solicitante: <strong>{{ $admission->requesting_doctor }}</strong>
        </div>
        @endif
    </div>
    
    <!-- Determinations -->
    <table class="det-table" cellpadding="0" cellspacing="0">
        <tr class="det-section-header">
            <td colspan="4">DETERMINACIONES</td>
        </tr>
        <tr class="det-col-header">
            <td width="40%">Análisis</td>
            <td width="18%" style="text-align: center;">Resultado</td>
            <td width="12%" style="text-align: center;">Unidad</td>
            <td width="30%" style="text-align: right;">Valores de ref.</td>
        </tr>

        @foreach($orderedTests as $entry)
            @php
                $at = $entry['at'];
                $level = $entry['level'];
                $isParent = $entry['isParent'];
                $isChild = $entry['isChild'];
                $isSub = $entry['isSubParent'];
                $indent = $level * 20;
            @endphp

            @if($isParent && !$isSub)
                <tr class="det-parent">
                    <td colspan="4">{{ strtoupper($at->test->name ?? 'N/A') }}</td>
                </tr>
                @if($at->test?->method)
                    <tr class="det-method">
                        <td colspan="4">{{ $at->test->method }}</td>
                    </tr>
                @endif
            @elseif($isSub)
                <tr class="det-parent">
                    <td colspan="4" style="padding-left: {{ $indent }}px;">
                        {{ strtoupper($at->test->name ?? 'N/A') }}
                    </td>
                </tr>
                @if($at->test?->method)
                    <tr class="det-method">
                        <td colspan="4" style="padding-left: {{ $indent }}px;">{{ $at->test->method }}</td>
                    </tr>
                @endif
            @elseif($isChild)
                <tr class="det-child">
                    <td style="padding-left: {{ $indent }}px;">{{ ucfirst($at->test->name ?? 'N/A') }}</td>
                    <td class="det-result">{{ $at->result ?? '-' }}</td>
                    <td class="det-unit">{{ $at->unit ?? $at->test->unit ?? '' }}</td>
                    <td class="det-ref">{{ $at->reference_value ?: ($at->test->other_reference ?? '') }}</td>
                </tr>
            @else
                <tr class="det-standalone">
                    <td>{{ ucfirst($at->test->name ?? 'N/A') }}</td>
                    <td class="det-result">{{ $at->result ?? '-' }}</td>
                    <td class="det-unit">{{ $at->unit ?? $at->test->unit ?? '' }}</td>
                    <td class="det-ref">{{ $at->reference_value ?: ($at->test->other_reference ?? '') }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    <!-- Observations -->
    @if($admission->observations)
    <div class="observations-block">
        <div class="observations-title">OBSERVACIONES</div>
        <p>{{ $admission->observations }}</p>
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
                        @if($validator && $validator->signature_path && file_exists($validator->signatureAbsolutePath))
                            <img src="{{ $validator->signatureAbsolutePath }}"
                                 style="max-height: 60px; max-width: 200px; margin-bottom: 5px;">
                        @endif
                        <div class="signature-line">
                            <div class="validator-name">{{ strtoupper($validator->name ?? 'DRA. CLARA SILVINA') }}</div>
                            <div class="validator-title">Director Técnico</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
