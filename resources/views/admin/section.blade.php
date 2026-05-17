<x-admin-layout>
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">{{ $section['title'] }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $section['description'] }}</p>
    </div>

    @include('admin.partials.section-cards', ['items' => $section['items']])
</div>
</x-admin-layout>
