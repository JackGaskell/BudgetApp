@extends('layouts.budget')

@section('title', __('Dashboard'))

@section('content')
    @include('layouts.partials.month-navigation', ['targetRoute' => 'dashboard'])

    <section class="mb-6">
        <div class="rounded-xl bg-indigo-600 p-5 text-white shadow-sm">
            <p class="text-sm text-indigo-100">{{ __('Current balance') }}</p>
            <p class="mt-1 text-4xl font-bold">@money($current_balance)</p>
            @if ($is_current_month)
                <p class="mt-2 text-sm text-indigo-100">{{ __('Money available based on income and expenses dated up to today.') }}</p>
            @else
                <p class="mt-2 text-sm text-indigo-100">{{ __('Net income minus expenses for :month, counted up to the last day that counts as “actual” for this view.', ['month' => $view_month_label]) }}</p>
            @endif
        </div>
    </section>

    <section class="mb-6 space-y-3">
        <h2 class="text-lg font-bold text-gray-900">{{ __('Stats') }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Actual income') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($actual_income)</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Actual expenses') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($actual_expenses)</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Scheduled income') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($scheduled_income)</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('Expected later in :month.', ['month' => $view_month_label]) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Scheduled expenses') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($scheduled_expenses)</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('Still to come in :month.', ['month' => $view_month_label]) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Projected end-of-month balance') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($projected_end_of_month_balance)</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('What you should have left after scheduled income and expenses.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Days until broke') }}</p>
                @if (! $days_until_broke_available)
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Shown only when you are viewing the current calendar month.') }}</p>
                @elseif (is_null($days_until_broke))
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ __('Not enough data') }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Add expenses to estimate this.') }}</p>
                @else
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $days_until_broke }} {{ __('days') }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('At this pace, your budget lasts about') }} {{ $days_until_broke }} {{ __('more days.') }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="mb-6 space-y-3">
        <h2 class="text-lg font-bold text-gray-900">{{ __('Insights') }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 shadow-sm">
                <p class="text-sm text-gray-600">{{ __('Daily budget') }}</p>
                @if (! $is_current_month)
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('Open the current month to see a daily spending guide for the rest of this month.') }}</p>
                @elseif (is_null($daily_budget_remaining))
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ __('Not enough data') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('No days left in this month to calculate a daily budget.') }}</p>
                @elseif ($daily_budget_remaining < 0)
                    <p class="mt-1 text-3xl font-bold text-gray-900">@money(abs($daily_budget_remaining))</p>
                    <p class="mt-2 text-sm text-gray-700">{{ __('You need to reduce your spending by') }} <span class="font-semibold">@money(abs($daily_budget_remaining))</span> {{ __('per day to stay on track.') }}</p>
                @else
                    <p class="mt-1 text-3xl font-bold text-gray-900">@money($daily_budget_remaining)</p>
                    <p class="mt-2 text-sm text-gray-700">{{ __('You can spend') }} <span class="font-semibold">@money($daily_budget_remaining)</span> {{ __('per day for the rest of the month.') }}</p>
                @endif
            </div>
            <div class="{{ !is_null($projected_overspend_amount) ? 'border-red-100 bg-red-50' : 'border-green-100 bg-green-50' }} rounded-xl border p-4 shadow-sm">
                <p class="text-sm text-gray-600">{{ __('Month-end outlook') }}</p>
                @if (!is_null($projected_overspend_amount))
                    <p class="mt-2 text-lg font-bold text-red-700">{{ __('Based on this month’s income and expenses (including items dated later this month), you’re projected to finish the month short by') }} <span class="whitespace-nowrap">@money($projected_overspend_amount)</span>.</p>
                @elseif ($total_month_income <= 0 && $total_month_expenses <= 0)
                    <p class="mt-2 text-lg font-bold text-gray-900">{{ __('No data yet') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('Add income or expenses dated this month to see whether you’re projected to end the month above or below zero.') }}</p>
                @else
                    <p class="mt-2 text-lg font-bold text-green-800">{{ __('You’re projected to end this month with') }} <span class="whitespace-nowrap">@money($projected_end_of_month_balance)</span> {{ __('left, based on income and expenses dated this month (including scheduled).') }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="mb-8 space-y-3">
        <h2 class="text-lg font-bold text-gray-900">{{ __('Main') }}</h2>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('Spending breakdown') }}</h3>
                @if ($categories_with_totals->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No expenses dated this month yet (paid or scheduled). Add expenses to see category trends.') }}</p>
                @else
                    @php
                        $barColors = [
                            'bg-indigo-400',
                            'bg-sky-400',
                            'bg-emerald-400',
                            'bg-amber-400',
                            'bg-violet-400',
                            'bg-rose-400',
                        ];
                        $barUpcomingColors = [
                            'bg-indigo-300',
                            'bg-sky-300',
                            'bg-emerald-300',
                            'bg-amber-300',
                            'bg-violet-300',
                            'bg-rose-300',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach ($categories_with_totals as $category)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ $category->category }}</p>
                                    <p class="shrink-0 text-right text-sm text-gray-600">
                                        @money($category->total)
                                        <span class="text-xs text-gray-500">({{ number_format($category->percentage, 0) }}%)</span>
                                    </p>
                                </div>
                                @if ($category->actual_total > 0 || $category->scheduled_total > 0)
                                    <p class="mb-1.5 text-xs text-gray-600">
                                        @if ($category->actual_total > 0 && $category->scheduled_total > 0)
                                            <span class="whitespace-nowrap">@money($category->actual_total)</span> {{ __('spent ·') }} <span class="whitespace-nowrap">@money($category->scheduled_total)</span> {{ __('upcoming') }}
                                        @elseif ($category->scheduled_total > 0)
                                            <span class="whitespace-nowrap">@money($category->scheduled_total)</span> {{ __('upcoming this month') }}
                                        @else
                                            <span class="whitespace-nowrap">@money($category->actual_total)</span> {{ __('spent so far') }}
                                        @endif
                                    </p>
                                @endif
                                @php
                                    $barWidthPct = min(100, $category->percentage);
                                    $ci = $loop->index % count($barColors);
                                @endphp
                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    @if ($category->actual_total > 0 && $category->scheduled_total > 0)
                                        <div class="flex h-2.5 overflow-hidden rounded-full" style="width: {{ $barWidthPct }}%;">
                                            <div class="h-full shrink-0 {{ $barColors[$ci] }}" style="width: {{ ($category->actual_total / $category->total) * 100 }}%;" title="{{ __('Spent so far') }}"></div>
                                            <div class="h-full shrink-0 {{ $barUpcomingColors[$ci] }} ring-1 ring-inset ring-black/10" style="width: {{ ($category->scheduled_total / $category->total) * 100 }}%;" title="{{ __('Upcoming this month') }}"></div>
                                        </div>
                                    @elseif ($category->scheduled_total > 0)
                                        <div class="h-2.5 rounded-full {{ $barUpcomingColors[$ci] }} ring-1 ring-inset ring-black/10" style="width: {{ $barWidthPct }}%;" title="{{ __('Upcoming this month') }}"></div>
                                    @else
                                        <div class="h-2.5 rounded-full {{ $barColors[$ci] }}" style="width: {{ $barWidthPct }}%;"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('Recent expenses') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-200 text-gray-600">
                            <tr>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Name') }}</th>
                                <th scope="col" class="px-2 py-3 text-center font-semibold">{{ __('Repeats') }}</th>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Category') }}</th>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Date') }}</th>
                                <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Amount') }} (£)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recent_expenses as $expense)
                                <tr>
                                    <td class="px-2 py-3 text-gray-800">{{ $expense->name }}</td>
                                    <td class="px-2 py-3 text-center align-top">
                                        @if ($expense->recurring_expense_id)
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium tabular-nums text-violet-800 sm:min-w-[2.5rem]" title="{{ __('Repeating') }}">{{ __('Yes') }}</span>
                                        @else
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium tabular-nums text-gray-700 sm:min-w-[2.5rem]">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-gray-700">{{ $expense->category }}</td>
                                    <td class="px-2 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($expense->date)->format('j M Y') }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-gray-900">@money($expense->amount)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-2 py-4 text-sm text-gray-500">{{ __('No expenses yet. Use Add transaction in the toolbar or press N.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

@endsection
