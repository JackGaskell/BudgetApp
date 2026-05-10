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
                <p class="text-xs text-gray-500">{{ __('Spending pace') }}</p>
                @if (is_null($spending_pace_daily_average))
                    <p class="mt-1 text-2xl font-bold text-gray-900">—</p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Shown after the month has started (not for months that have not begun yet).') }}</p>
                @else
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        <span class="whitespace-nowrap">@money($spending_pace_daily_average) <span class="text-lg font-semibold text-gray-600">{{ __('per day') }}</span></span>
                    </p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Average actual spend per day in :month so far.', ['month' => $view_month_label]) }}</p>
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
                @php
                    $dashEditIcon = '<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>';
                    $dashDeleteIcon = '<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>';
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-200 text-gray-600">
                            <tr>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Name') }}</th>
                                <th scope="col" class="px-2 py-3 text-center font-semibold">{{ __('Repeats') }}</th>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Category') }}</th>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Date') }}</th>
                                <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Amount') }} (£)</th>
                                <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Actions') }}</th>
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
                                    <td class="whitespace-nowrap px-2 py-3 text-right align-top">
                                        <div class="inline-flex items-center justify-end gap-0.5">
                                            <a href="{{ route('expenses.edit', array_merge(['expense' => $expense], \App\Support\ViewMonth::queryParams($view_year, $view_month))) }}" class="inline-flex items-center justify-center rounded-md p-1.5 text-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-1" title="{{ __('Edit') }}">
                                                {!! $dashEditIcon !!}
                                                <span class="sr-only">{{ __('Edit') }}</span>
                                            </a>
                                            <button type="button" class="inline-flex items-center justify-center rounded-md p-1.5 text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-1" title="{{ __('Delete') }}" onclick="document.getElementById('dashboard-delete-expense-{{ $expense->id }}').showModal()">
                                                {!! $dashDeleteIcon !!}
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-2 py-4 text-sm text-gray-500">{{ __('No expenses yet. Use Add transaction in the toolbar or press N.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @foreach ($recent_expenses as $expense)
                    <dialog id="dashboard-delete-expense-{{ $expense->id }}" class="w-[calc(100vw-2rem)] max-w-md rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-black/40">
                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h3 class="text-lg font-bold text-gray-900">{{ __('Delete expense?') }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ __('This will remove “:name” (:amount) from your records.', ['name' => $expense->name, 'amount' => \App\Support\Money::format($expense->amount)]) }}</p>
                            <div class="mt-6 flex flex-wrap justify-end gap-2">
                                <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2" onclick="this.closest('dialog').close()">{{ __('Cancel') }}</button>
                                <button type="submit" class="rounded-lg border border-red-700 bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">{{ __('Delete') }}</button>
                            </div>
                        </form>
                    </dialog>
                @endforeach
            </div>
        </div>
    </section>

@endsection
