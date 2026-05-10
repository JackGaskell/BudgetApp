@php
    $targetRoute = $targetRoute ?? 'dashboard';
    $viewingCurrentMonth = \App\Support\ViewMonth::isCurrentMonth($view_year, $view_month);
    $currentMonthLabel = now()->translatedFormat('F Y');
    $currentRouteParams = \App\Support\ViewMonth::queryParams(now()->year, now()->month);
@endphp
<div class="mb-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4">
    <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Viewing') }}</p>
        <p class="text-xl font-bold leading-tight text-gray-900">{{ $view_month_label }}</p>
    </div>
    <nav class="flex flex-wrap items-center justify-start gap-2 sm:justify-end" aria-label="{{ __('Month navigation') }}">
        @if (! $viewingCurrentMonth)
            <a
                href="{{ route($targetRoute, $currentRouteParams) }}"
                class="inline-flex items-center justify-center rounded-lg border border-indigo-600 bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2"
            >
                {{ __('Return to :month', ['month' => $currentMonthLabel]) }}
            </a>
        @endif
        <a
            href="{{ route($targetRoute, $prev_period_params) }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
        >
            ← {{ __('Previous') }}
        </a>
        <a
            href="{{ route($targetRoute, $next_period_params) }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
        >
            {{ __('Next') }} →
        </a>
    </nav>
</div>
