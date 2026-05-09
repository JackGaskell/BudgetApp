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
            <p class="mt-3 text-sm text-indigo-200">
                <a href="{{ route('records.index', \App\Support\ViewMonth::queryParams(now()->year, now()->month)) }}" class="font-semibold underline decoration-indigo-200 underline-offset-2 hover:text-white">{{ __('Records') }}</a>
                <span class="text-indigo-200/90"> — {{ __('Repeating items are labelled there; edit a line to turn monthly repeat on or off.') }}</span>
            </p>
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
                <p class="mt-2 text-xs text-gray-500">{{ __('Income expected later this month.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Scheduled expenses') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">@money($scheduled_expenses)</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('Future bills or spending still to come this month.') }}</p>
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
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Category') }}</th>
                                <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Date') }}</th>
                                <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Amount') }} (£)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recent_expenses as $expense)
                                <tr>
                                    <td class="px-2 py-3 text-gray-800">
                                        {{ $expense->name }}
                                        @if ($expense->recurring_expense_id)
                                            <span class="ml-1 inline-flex rounded-full bg-violet-100 px-1.5 py-0.5 text-xs font-medium text-violet-800" title="{{ __('Repeats every month') }}">{{ __('Repeats') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-gray-700">{{ $expense->category }}</td>
                                    <td class="px-2 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($expense->date)->format('j M Y') }}</td>
                                    <td class="px-2 py-3 text-right font-semibold text-gray-900">@money($expense->amount)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-2 py-4 text-sm text-gray-500">{{ __('No expenses yet. Add one below.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-6 space-y-3">
        <h2 class="text-lg font-bold text-gray-900">{{ __('Add transactions') }}</h2>
        <p class="text-sm text-gray-500">{{ __('All amounts are in pounds sterling (GBP).') }}</p>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('Add income') }}</h3>
                <form method="POST" action="{{ route('income.store') }}" class="space-y-4" novalidate>
                    @csrf
                    <div>
                        <label for="income_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                        <input id="income_name" name="income_name" type="text" value="{{ old('income_name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('income_name') border-red-500 @enderror">
                        @error('income_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="income_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                        <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('income_amount') border-red-500 @enderror">
                            <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600" aria-hidden="true">£</span>
                            <input id="income_amount" name="income_amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('income_amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                        </div>
                        @error('income_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="income_date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                        <input id="income_date" name="income_date" type="date" value="{{ old('income_date', $default_transaction_date) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('income_date') border-red-500 @enderror">
                        @error('income_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-start gap-3">
                        <input id="income_recurring" name="income_recurring" type="checkbox" value="1" @checked(old('income_recurring')) class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="income_recurring" class="text-sm text-gray-700">{{ __('Repeat every month on this calendar day (you can change this when you edit the income in Records).') }}</label>
                    </div>
                    <button type="submit" class="w-full rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 sm:w-auto">{{ __('Add income') }}</button>
                </form>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('Add expense') }}</h3>
                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4" novalidate>
                    @csrf
                    <div>
                        <label for="expense_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                        <input id="expense_name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="expense_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                        <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('amount') border-red-500 @enderror">
                            <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600" aria-hidden="true">£</span>
                            <input id="expense_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="expense_category" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                        <select id="expense_category" name="category" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 @error('category') border-red-500 @enderror">
                            <option value="" disabled @selected(! old('category'))>{{ __('Select a category') }}</option>
                            @foreach ($expense_categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="expense_date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                        <input id="expense_date" name="date" type="date" value="{{ old('date', $default_transaction_date) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('date') border-red-500 @enderror">
                        @error('date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-start gap-3">
                        <input id="expense_recurring" name="recurring" type="checkbox" value="1" @checked(old('recurring')) class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="expense_recurring" class="text-sm text-gray-700">{{ __('Repeat every month on this calendar day (you can change this when you edit the expense in Records).') }}</label>
                    </div>
                    <button type="submit" class="w-full rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 sm:w-auto">{{ __('Add expense') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
