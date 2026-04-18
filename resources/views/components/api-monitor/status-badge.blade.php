@props(['status'])

@php
    $config = [
        'ingested'   => ['label' => 'Ingestado',  'class' => 'bg-green-100 text-green-700'],
        'partial'    => ['label' => 'Parcial',     'class' => 'bg-yellow-100 text-yellow-700'],
        'rejected'   => ['label' => 'Rechazado',   'class' => 'bg-red-100 text-red-700'],
        'duplicate'  => ['label' => 'Duplicado',   'class' => 'bg-gray-100 text-gray-600'],
        'overwritten'=> ['label' => 'Sobrescrito', 'class' => 'bg-orange-100 text-orange-700'],
    ];
    $cfg = $config[$status] ?? ['label' => $status, 'class' => 'bg-gray-100 text-gray-600'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $cfg['class'] }}">
    {{ $cfg['label'] }}
</span>
