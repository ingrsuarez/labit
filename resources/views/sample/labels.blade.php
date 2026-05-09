<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas - {{ $sample->protocol_number }}</title>
    <style>
        @page {
            size: landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
        }

        .label {
            width: 60mm;
            height: 40mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2mm 3mm;
            page-break-after: always;
        }

        .barcode {
            text-align: center;
            margin-bottom: 1mm;
        }

        .barcode svg {
            width: auto;
            height: 12mm;
        }

        .protocol {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .customer {
            font-size: 7pt;
            text-align: center;
            margin-top: 1mm;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .meta {
            font-size: 6pt;
            text-align: center;
            margin-top: 1mm;
            color: #333;
        }

        .meta strong {
            font-size: 7pt;
        }

        @media print {
            html, body {
                width: 60mm;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .label {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }

        @media screen {
            .label {
                border: 1px dashed #ccc;
                margin-bottom: 10px;
            }

            .no-print {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 100;
            }

            .no-print button {
                padding: 8px 20px;
                background: #7c3aed;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                cursor: pointer;
            }

            .no-print button:hover {
                background: #6d28d9;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimir ({{ count($labels) }} etiqueta{{ count($labels) > 1 ? 's' : '' }})</button>
    </div>

    @foreach($labels as $label)
    <div class="label">
        <div class="barcode">{!! $label['barcode_svg'] !!}</div>
        <div class="protocol">{{ $label['protocol_number'] }}</div>
        <div class="customer">{{ Str::limit($label['customer_name'], 35) }}</div>
        <div class="meta">
            {{ $label['branch_name'] ?? strtoupper($sample->sample_type) }} | <strong>{{ $label['material'] }}</strong> | {{ $label['entry_date'] }}
        </div>
    </div>
    @endforeach
</body>
</html>
