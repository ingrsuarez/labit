@props(['health'])

@php
    $config = [
        'healthy'    => ['label' => 'Activo',         'class' => 'bg-green-100 text-green-700'],
        'idle'       => ['label' => 'Inactivo (hs)',   'class' => 'bg-yellow-100 text-yellow-700'],
        'stale'      => ['label' => 'Sin datos (días)','class' => 'bg-orange-100 text-orange-700'],
        'inactive'   => ['label' => 'Desconectado',   'class' => 'bg-red-100 text-red-700'],
        'never_used' => ['label' => 'Nunca usado',    'class' => 'bg-gray-100 text-gray-600'],
    ];
    $cfg = $config[$health] ?? $config['never_used'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $cfg['class'] }}">
    {{ $cfg['label'] }}
</span>
