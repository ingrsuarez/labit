{{-- Toast fijo para acciones Livewire en protocolos (no desplaza el layout). --}}
<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-on:notify.window="
        message = $event.detail.message ?? '';
        type = $event.detail.type ?? 'success';
        show = true;
        clearTimeout($el._toastTimer);
        $el._toastTimer = setTimeout(() => show = false, 4000);
    "
    x-show="show"
    x-cloak
    x-transition
    class="fixed bottom-4 right-4 z-[100] max-w-sm rounded-lg px-4 py-3 text-sm font-medium shadow-lg pointer-events-none"
    :class="type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'"
    role="status"
    aria-live="polite"
    x-text="message"
></div>
