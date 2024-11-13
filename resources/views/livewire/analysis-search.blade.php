<!-- resources/views/livewire/analysis-search.blade.php -->

<div class="relative" wire:model="search" wire:keyup="updateSearch" wire:keydown.escape="hideDropdown"
    wire:keydown.arrow-down.prevent="incrementHighlight"  
    wire:keydown.arrow-up.prevent="decrementHighlight"    
    wire:keydown.enter.prevent="selectHighlightedAnalysis">
    <!-- Campo de entrada de búsqueda -->
    <input type="text" 
           placeholder="Buscar análisis..." 
           class="border border-gray-300 rounded-lg p-2 w-full"
           wire:model.debounce.300ms="search"
           wire:focus="showDropdown = true" 
           wire:blur="hideDropdown" /> <!-- Oculta el menú al perder el enfoque -->

    <!-- Menú desplegable de resultados -->
    @if($showDropdown)
        <ul class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded-lg shadow-lg">
            @forelse($analyses as $index => $analysis)
                <li class="p-2 hover:bg-gray-100 cursor-pointer {{ $highlightIndex === $index ? 'bg-gray-200' : '' }}"
                    wire:click="selectAnalysis({{ $analysis->id }})">
                    <span class="font-bold">{{ strtoupper($analysis->name) }}</span>
                    <span class="text-gray-500">- {{ $analysis->code }}</span>
                </li>
            @empty
                <!-- Mensaje cuando no hay resultados -->
                <li class="p-2 text-gray-500">No se encontraron resultados.</li>
            @endforelse
        </ul>
    @endif
</div>

