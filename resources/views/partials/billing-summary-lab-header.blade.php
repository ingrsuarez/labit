@php
    $lab = billing_summary_lab();
@endphp
<div class="flex flex-col sm:flex-row sm:items-start gap-4 mb-6 pb-4 border-b border-gray-200">
    @if($lab['has_logo'])
        <img src="{{ $lab['logo_url'] }}" alt="IPAC" class="h-16 w-auto shrink-0">
    @endif
    <div class="min-w-0">
        @if(! empty($reportTitle))
            <p class="text-lg font-bold text-gray-900">{{ $reportTitle }}</p>
        @endif
        <p class="{{ ! empty($reportTitle) ? 'mt-1' : '' }} text-base font-semibold text-gray-800">{{ $lab['name'] }}</p>
        <p class="text-sm text-gray-600 mt-0.5">CUIT: {{ $lab['cuit'] }}</p>
        <p class="text-sm text-gray-600">Domicilio: {{ $lab['address_line'] }}</p>
        @if(! empty($reportSubtitle))
            <p class="text-sm text-gray-500 mt-2">{{ $reportSubtitle }}</p>
        @endif
        @if(! empty($counterpartyLabel))
            <p class="text-sm font-medium text-gray-700 mt-1">{{ $counterpartyLabel }}</p>
        @endif
    </div>
</div>
