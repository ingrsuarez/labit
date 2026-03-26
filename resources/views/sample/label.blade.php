<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta - {{ $sample->protocol_number }}</title>
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
            width: 60mm;
            height: 40mm;
            font-family: Arial, Helvetica, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2mm 3mm;
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

        @media print {
            html {
                width: 60mm;
                height: 40mm;
            }

            body {
                width: 60mm;
                height: 40mm;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        @media screen {
            body {
                border: 1px dashed #ccc;
                margin: 20px auto;
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
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="barcode">{!! $barcodeSvg !!}</div>
    <div class="protocol">{{ $sample->protocol_number }}</div>
    <div class="customer">{{ Str::limit($sample->customer->name ?? 'N/A', 35) }}</div>
    <div class="meta">
        {{ strtoupper($sample->sample_type) }} | MAT: {{ $materials }} | {{ $sample->entry_date->format('d/m/Y') }}
    </div>
</body>
</html>
