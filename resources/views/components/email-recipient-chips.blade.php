@props([
    'entityEmails' => collect(),
    'inputId' => 'emailInput',
    'accent' => 'teal',
])

@php
    $chipClass = match ($accent) {
        'amber' => 'border-amber-400 text-amber-700 bg-amber-50 hover:bg-amber-100',
        'purple' => 'border-purple-400 text-purple-700 bg-purple-50 hover:bg-purple-100',
        'teal' => 'border-teal-400 text-teal-700 bg-teal-50 hover:bg-teal-100',
        default => 'border-gray-400 text-gray-700 bg-gray-100 hover:bg-gray-200',
    };
    $allJoined = $entityEmails->pluck('email')->implode(', ');
@endphp

@if($entityEmails->isNotEmpty())
    <div class="flex flex-wrap gap-1 mb-2">
        @foreach($entityEmails as $entityEmail)
            @php
                $chipLabel = $entityEmail->label
                    ? $entityEmail->label.' · '.$entityEmail->email
                    : $entityEmail->email;
            @endphp
            <button type="button"
                    onclick="document.getElementById('{{ $inputId }}').value = @js($entityEmail->email)"
                    class="text-xs px-2 py-1 rounded-full border {{ $chipClass }} truncate max-w-full">
                {{ $chipLabel }}
            </button>
        @endforeach
        @if($entityEmails->count() > 1)
            <button type="button"
                    onclick="document.getElementById('{{ $inputId }}').value = @js($allJoined)"
                    class="text-xs px-2 py-1 rounded-full border border-gray-400 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium">
                Todos ({{ $entityEmails->count() }})
            </button>
        @endif
    </div>
@endif
