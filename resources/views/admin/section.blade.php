<x-admin-layout>
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">{{ $section['title'] }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $section['description'] }}</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($section['items'] as $item)
            <a href="{{ $item['route'] }}"
               class="flex flex-col items-center gap-3 px-4 py-5 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-zinc-400 transition-all group">
                <div class="w-10 h-10 rounded-lg bg-zinc-100 flex items-center justify-center group-hover:bg-zinc-200 transition-colors">
                    <svg class="w-5 h-5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </div>
                <div class="text-center">
                    <span class="block text-sm font-semibold text-gray-800 group-hover:text-zinc-700 transition-colors">{{ $item['name'] }}</span>
                    <span class="block text-xs text-gray-400 mt-0.5">{{ $item['description'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
</x-admin-layout>
