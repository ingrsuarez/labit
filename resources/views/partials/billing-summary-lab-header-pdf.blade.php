@php
    $lab = billing_summary_lab();
@endphp
<table class="lab-header" cellspacing="0" cellpadding="0">
    <tr>
        <td class="lab-header-logo">
            @if($lab['has_logo'])
                <img src="{{ $lab['logo_path'] }}" alt="IPAC" class="logo-img">
            @endif
        </td>
        <td class="lab-header-info">
            @if(! empty($reportTitle))
                <div class="report-title">{{ $reportTitle }}</div>
            @endif
            <div class="lab-name">{{ $lab['name'] }}</div>
            <div class="lab-detail">CUIT: {{ $lab['cuit'] }}</div>
            <div class="lab-detail">Domicilio: {{ $lab['address_line'] }}</div>
            @if(! empty($reportSubtitle))
                <div class="report-subtitle">{{ $reportSubtitle }}</div>
            @endif
            @if(! empty($counterpartyLabel))
                <div class="counterparty">{{ $counterpartyLabel }}</div>
            @endif
        </td>
    </tr>
</table>
