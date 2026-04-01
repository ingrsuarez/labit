@php
    $hasChildren = $account->children->count() > 0;
    $indent = $depth * 20;
    $typeColors = [
        'activo' => 'bg-blue-100 text-blue-800',
        'pasivo' => 'bg-red-100 text-red-800',
        'patrimonio_neto' => 'bg-purple-100 text-purple-800',
        'resultado_positivo' => 'bg-green-100 text-green-800',
        'resultado_negativo' => 'bg-orange-100 text-orange-800',
    ];
    $typeLabels = [
        'activo' => 'Activo',
        'pasivo' => 'Pasivo',
        'patrimonio_neto' => 'Pat. Neto',
        'resultado_positivo' => 'Res. (+)',
        'resultado_negativo' => 'Res. (−)',
    ];
    $uniqueId = 'acct_' . $account->id;
@endphp
<tbody x-data="{ open: false }" id="{{ $uniqueId }}">
<tr class="{{ $account->is_header && $depth === 0 ? 'bg-gray-50' : '' }} {{ !$account->is_active ? 'opacity-50' : '' }}">
    <td class="px-4 py-2.5 text-center">
        @if($hasChildren)
        <button @click="open = !open" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        @endif
    </td>
    <td class="px-4 py-2.5 text-sm {{ $account->is_header ? 'font-bold' : '' }}" style="padding-left: {{ 16 + $indent }}px">
        <span class="font-mono text-gray-600">{{ $account->code }}</span>
    </td>
    <td class="px-4 py-2.5 text-sm {{ $account->is_header ? 'font-bold text-gray-800' : 'text-gray-700' }}" style="padding-left: {{ $indent }}px">
        {{ $account->name }}
    </td>
    <td class="px-4 py-2.5">
        @if($depth === 0)
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$account->type] ?? 'bg-gray-100 text-gray-800' }}">
            {{ $typeLabels[$account->type] ?? $account->type }}
        </span>
        @endif
    </td>
    <td class="px-4 py-2.5 text-center text-sm text-gray-500">{{ $account->level }}</td>
    <td class="px-4 py-2.5 text-center">
        @if(!$account->is_active)
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactiva</span>
        @endif
    </td>
    <td class="px-4 py-2.5 text-right">
        <div class="flex items-center justify-end gap-1">
            @can('contabilidad.accounts.edit')
            <a href="{{ route('accounting.accounts.edit', $account) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded transition-colors" title="Editar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
            @endcan
            @can('contabilidad.accounts.delete')
            @if($account->is_active && !$account->is_header)
            <form action="{{ route('accounting.accounts.destroy', $account) }}" method="POST" class="inline" onsubmit="return confirm('¿Desactivar esta cuenta?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition-colors" title="Desactivar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </button>
            </form>
            @endif
            @endcan
        </div>
    </td>
</tr>
@if($hasChildren)
    @foreach($account->children as $child)
        <tr x-show="open" x-transition.opacity class="{{ !$child->is_active ? 'opacity-50' : '' }}">
            <td class="px-4 py-2.5 text-center">
                @if($child->children->count() > 0)
                    <span class="text-gray-300 text-xs">┗</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-sm {{ $child->is_header ? 'font-bold' : '' }}" style="padding-left: {{ 16 + ($depth + 1) * 20 }}px">
                <span class="font-mono text-gray-600">{{ $child->code }}</span>
            </td>
            <td class="px-4 py-2.5 text-sm {{ $child->is_header ? 'font-bold text-gray-800' : 'text-gray-700' }}" style="padding-left: {{ ($depth + 1) * 20 }}px">
                {{ $child->name }}
            </td>
            <td class="px-4 py-2.5"></td>
            <td class="px-4 py-2.5 text-center text-sm text-gray-500">{{ $child->level }}</td>
            <td class="px-4 py-2.5 text-center">
                @if(!$child->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactiva</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">
                <div class="flex items-center justify-end gap-1">
                    @can('contabilidad.accounts.edit')
                    <a href="{{ route('accounting.accounts.edit', $child) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded transition-colors" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    @endcan
                    @can('contabilidad.accounts.delete')
                    @if($child->is_active && !$child->is_header)
                    <form action="{{ route('accounting.accounts.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('¿Desactivar esta cuenta?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition-colors" title="Desactivar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </td>
        </tr>
        @foreach($child->children as $grandchild)
        <tr x-show="open" x-transition.opacity class="{{ !$grandchild->is_active ? 'opacity-50' : '' }}">
            <td class="px-4 py-2.5"></td>
            <td class="px-4 py-2.5 text-sm" style="padding-left: {{ 16 + ($depth + 2) * 20 }}px">
                <span class="font-mono text-gray-600">{{ $grandchild->code }}</span>
            </td>
            <td class="px-4 py-2.5 text-sm text-gray-700" style="padding-left: {{ ($depth + 2) * 20 }}px">
                {{ $grandchild->name }}
            </td>
            <td class="px-4 py-2.5"></td>
            <td class="px-4 py-2.5 text-center text-sm text-gray-500">{{ $grandchild->level }}</td>
            <td class="px-4 py-2.5 text-center">
                @if(!$grandchild->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactiva</span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">
                <div class="flex items-center justify-end gap-1">
                    @can('contabilidad.accounts.edit')
                    <a href="{{ route('accounting.accounts.edit', $grandchild) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded transition-colors" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    @endcan
                    @can('contabilidad.accounts.delete')
                    @if($grandchild->is_active)
                    <form action="{{ route('accounting.accounts.destroy', $grandchild) }}" method="POST" class="inline" onsubmit="return confirm('¿Desactivar esta cuenta?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition-colors" title="Desactivar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </td>
        </tr>
        @endforeach
    @endforeach
@endif
</tbody>
