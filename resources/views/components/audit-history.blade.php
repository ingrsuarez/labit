@props(['logs'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center">
            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Historial de actividad
        </h3>
    </div>

    @if($logs->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400 text-sm">
            Sin actividad registrada.
        </div>
    @else
        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
            @foreach($logs as $log)
            <div class="px-6 py-3 flex items-start gap-3 hover:bg-gray-50">
                @php
                    $colors = match($log->action) {
                        'created' => 'bg-green-100 text-green-700',
                        'updated' => 'bg-blue-100 text-blue-700',
                        'deleted' => 'bg-red-100 text-red-700',
                        'validated' => 'bg-teal-100 text-teal-700',
                        'unvalidated' => 'bg-amber-100 text-amber-700',
                        'results_loaded' => 'bg-indigo-100 text-indigo-700',
                        'pdf_generated' => 'bg-purple-100 text-purple-700',
                        'email_sent' => 'bg-cyan-100 text-cyan-700',
                        'login' => 'bg-green-100 text-green-700',
                        'logout' => 'bg-gray-100 text-gray-700',
                        'login_failed' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    };
                    $icons = match($log->action) {
                        'created' => '+',
                        'updated' => '✎',
                        'deleted' => '✕',
                        'validated' => '✓',
                        'unvalidated' => '↩',
                        'results_loaded' => '📋',
                        'pdf_generated' => '📄',
                        'email_sent' => '✉',
                        'login' => '→',
                        'logout' => '←',
                        'login_failed' => '⚠',
                        default => '•',
                    };
                @endphp
                <span class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $colors }}">
                    {{ $icons }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $log->user_name }}
                        &middot;
                        {{ $log->created_at->diffForHumans() }}
                        @if($log->ip_address)
                            &middot; <span class="font-mono">{{ $log->ip_address }}</span>
                        @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
