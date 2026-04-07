<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de cobro {{ $collectionReceipt->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; padding: 12px 16px; }

        .company-block { margin-bottom: 12px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .company-logo { max-height: 45px; margin-bottom: 8px; display: block; }
        .company-name { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .company-detail { font-size: 9px; color: #555; line-height: 1.5; }

        .doc-title { font-size: 16px; font-weight: bold; text-align: center; margin: 10px 0 8px; letter-spacing: 0.05em; }
        .meta-row { font-size: 10px; margin-bottom: 4px; }
        .meta-label { color: #666; display: inline-block; min-width: 100px; }
        .status-badge { display: inline-block; padding: 2px 8px; border: 1px solid #333; font-weight: bold; font-size: 9px; margin-left: 6px; }

        .client-box { width: 100%; border: 1px solid #999; margin-top: 8px; padding: 8px 12px; margin-bottom: 12px; }
        .client-box td { padding: 2px 0; font-size: 10px; }
        .client-label { color: #666; width: 120px; }
        .client-value { font-weight: bold; }

        .section-title { font-size: 11px; font-weight: bold; margin: 12px 0 6px; text-transform: uppercase; color: #222; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .data-table th {
            background: #333; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; text-transform: uppercase;
        }
        .data-table th.right { text-align: right; }
        .data-table td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
        .data-table td.right { text-align: right; }
        .data-table tfoot td { font-weight: bold; background: #f5f5f5; border-top: 1px solid #999; }

        .total-bar { margin-top: 10px; text-align: right; font-size: 12px; font-weight: bold; padding: 8px 0; border-top: 2px solid #333; }

        .notes-box { margin-top: 10px; padding: 6px 10px; background: #f5f5f5; font-size: 9px; }
        .legal { margin-top: 14px; font-size: 8px; color: #666; line-height: 1.45; font-style: italic; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
@php
    $company = $collectionReceipt->company;
    $customer = $collectionReceipt->customer;
    $paymentMethodLabels = [
        'transferencia' => 'Transferencia',
        'cheque' => 'Cheque / e-cheq (legado)',
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'deposito' => 'Depósito',
    ];
@endphp

    <div class="company-block">
        @if(file_exists(public_path('images/logo_ipac.png')))
            <img src="{{ public_path('images/logo_ipac.png') }}" class="company-logo" alt="IPAC">
        @endif
        @if($company)
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-detail">
                @if($company->cuit)
                    CUIT: {{ $company->cuit }}<br>
                @endif
                @if($company->address || $company->city || $company->state)
                    {{ trim(implode(' — ', array_filter([$company->address, $company->city, $company->state]))) }}<br>
                @endif
                @if($company->phone)
                    Tel: {{ $company->phone }}
                    @if($company->email) — {{ $company->email }} @endif
                @elseif($company->email)
                    {{ $company->email }}
                @endif
            </div>
        @endif
    </div>

    <div class="doc-title">RECIBO DE COBRO</div>

    <div class="meta-row"><span class="meta-label">Número</span>{{ $collectionReceipt->number }}</div>
    <div class="meta-row"><span class="meta-label">Fecha</span>{{ $collectionReceipt->date->format('d/m/Y') }}</div>
    <div class="meta-row">
        <span class="meta-label">Estado</span>{{ $collectionReceipt->status_label }}
    </div>

    <table class="client-box" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td class="client-label">Cliente</td>
            <td class="client-value">{{ $customer->name }}</td>
        </tr>
        @if($customer->taxId)
            <tr>
                <td class="client-label">CUIT / CUIL</td>
                <td class="client-value">{{ $customer->taxId }}</td>
            </tr>
        @endif
    </table>

    @if($collectionReceipt->payments->isNotEmpty())
        <div class="section-title">Medios de pago</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th class="right">Importe</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collectionReceipt->payments as $pay)
                    <tr>
                        <td>{{ $pay->line_type_label }}</td>
                        <td class="right">${{ number_format((float) $pay->amount, 2, ',', '.') }}</td>
                        <td>
                            @if($pay->line_type === 'transferencia')
                                {{ $pay->bankAccount?->display_name ?? '—' }}
                            @elseif($pay->line_type === 'echeq')
                                N° {{ $pay->cheque_number }} — {{ $pay->bank_name }} — vto. {{ $pay->due_date?->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($collectionReceipt->payment_method)
        <div class="section-title">Pago (registro legado)</div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td>{{ $paymentMethodLabels[$collectionReceipt->payment_method] ?? $collectionReceipt->payment_method }}</td>
                    <td class="right">${{ number_format((float) $collectionReceipt->total, 2, ',', '.') }}</td>
                </tr>
                @if($collectionReceipt->payment_reference)
                    <tr>
                        <td colspan="2">Referencia: {{ $collectionReceipt->payment_reference }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if($collectionReceipt->withholdings->isNotEmpty())
        <div class="section-title">Retenciones sufridas</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nº doc.</th>
                    <th>Régimen</th>
                    <th>Jurisd.</th>
                    <th>Nº certif.</th>
                    <th class="right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collectionReceipt->withholdings as $wh)
                    <tr>
                        <td>{{ \App\Models\CollectionReceiptWithholding::typeLabel($wh->withholding_type) }}</td>
                        <td>{{ $wh->document_number ?: '—' }}</td>
                        <td>{{ $wh->regime }}</td>
                        <td>{{ $wh->jurisdiction ?: '—' }}</td>
                        <td>{{ $wh->certificate_number }}</td>
                        <td class="right">${{ number_format((float) $wh->amount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="right">Total retenciones</td>
                    <td class="right">${{ number_format((float) $collectionReceipt->withholdings->sum('amount'), 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="section-title">Facturas incluidas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Comprobante</th>
                <th class="right">Monto imputado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($collectionReceipt->items as $item)
                <tr>
                    <td>{{ $item->invoice->full_number }}</td>
                    <td class="right">${{ number_format((float) $item->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-bar">
        Total del recibo: ${{ number_format((float) $collectionReceipt->total, 2, ',', '.') }}
    </div>

    @if($collectionReceipt->notes)
        <div class="notes-box">
            <strong>Notas:</strong> {{ $collectionReceipt->notes }}
        </div>
    @endif

    <p class="legal">
        Documento de constancia de cobro emitido por {{ $company?->name ?? 'el laboratorio' }}. No reemplaza ni equivale a una factura fiscal electrónica AFIP.
    </p>
</body>
</html>
