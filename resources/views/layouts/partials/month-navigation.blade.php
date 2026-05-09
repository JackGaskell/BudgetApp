@php
    $targetRoute = $targetRoute ?? 'dashboard';
@endphp
<div class="mb-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Viewing') }}</p>
        <p class="text-xl font-bold text-gray-900">{{ $view_month_label }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a
            href="{{ route($targetRoute, $prev_period_params) }}"
            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
        >
            ← {{ __('Previous') }}
        </a>
        @if (! \App\Support\ViewMonth::isCurrentMonth($view_year, $view_month))
            <a
                href="{{ route($targetRoute, \App\Support\ViewMonth::queryParams(now()->year, now()->month)) }}"
                class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2"
            >
                {{ __('This month') }}
            </a>
        @endif
        <a
            href="{{ route($targetRoute, $next_period_params) }}"
            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
        >
            {{ __('Next') }} →
        </a>
    </div>
</div>
