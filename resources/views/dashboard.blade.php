<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="w-full bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <p class="text-lg font-bold text-gray-900">SpendSense</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('records.index') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Records</a>
                <p class="text-sm text-gray-600">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-sm font-semibold px-4 py-2 rounded-lg border border-gray-900">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-6">
        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <section class="mb-6">
            <div class="bg-indigo-600 rounded-xl shadow-sm p-5 text-white">
                <p class="text-sm text-indigo-100">Current Balance</p>
                <p class="text-4xl font-bold mt-1">£{{ number_format($current_balance, 2) }}</p>
                <p class="text-sm text-indigo-100 mt-2">Money available based on income and expenses dated up to today.</p>
            </div>
        </section>

        <section class="space-y-3 mb-6">
            <h2 class="text-lg font-bold text-gray-900">Stats</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Actual Income</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">£{{ number_format($actual_income, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Actual Expenses</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">£{{ number_format($actual_expenses, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Scheduled Income</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">£{{ number_format($scheduled_income, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Income expected later this month.</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Scheduled Expenses</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">£{{ number_format($scheduled_expenses, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Future bills or spending still to come this month.</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Projected End-of-Month Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">£{{ number_format($projected_end_of_month_balance, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-2">What you should have left after scheduled income and expenses.</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Days Until Broke</p>
                    @if (is_null($days_until_broke))
                        <p class="text-2xl font-bold text-gray-900 mt-1">Not enough data</p>
                        <p class="text-xs text-gray-500 mt-2">Add expenses to estimate this.</p>
                    @else
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $days_until_broke }} days</p>
                        <p class="text-xs text-gray-500 mt-2">At this pace, your budget lasts about {{ $days_until_broke }} more days.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="space-y-3 mb-6">
            <h2 class="text-lg font-bold text-gray-900">Insights</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 border border-blue-100 rounded-xl shadow-sm p-4">
                    <p class="text-sm text-gray-500">Daily Budget</p>
                    @if (is_null($daily_budget_remaining))
                        <p class="text-2xl font-bold text-gray-900 mt-1">Not enough data</p>
                        <p class="text-sm text-gray-500 mt-2">No days left in this month to calculate a daily budget.</p>
                    @elseif ($daily_budget_remaining < 0)
                        <p class="text-3xl font-bold text-gray-900 mt-1">£{{ number_format(abs($daily_budget_remaining), 2) }}</p>
                        <p class="text-sm text-gray-500 mt-2">You need to reduce your spending by £{{ number_format(abs($daily_budget_remaining), 2) }} per day to stay on track.</p>
                    @else
                        <p class="text-3xl font-bold text-gray-900 mt-1">£{{ number_format($daily_budget_remaining, 2) }}</p>
                        <p class="text-sm text-gray-500 mt-2">You can spend £{{ number_format($daily_budget_remaining, 2) }} per day for the rest of the month.</p>
                    @endif
                </div>
                <div class="{{ !is_null($projected_overspend_amount) || (!is_null($overspend_amount) && $overspend_amount > 0) ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100' }} border rounded-xl shadow-sm p-4">
                    <p class="text-sm text-gray-500">Overspending Warning</p>
                    @if (is_null($overspend_amount))
                        <p class="text-lg font-bold text-gray-900 mt-2">Not enough data</p>
                        <p class="text-sm text-gray-500 mt-2">Add income to compare expected vs actual spending.</p>
                    @elseif (!is_null($projected_overspend_amount))
                        <p class="text-lg font-bold text-red-600 mt-2">You're on track with your current spending, but based on upcoming bills, you're projected to overspend by £{{ number_format($projected_overspend_amount, 2) }} this month.</p>
                    @elseif ($overspend_amount > 0)
                        <p class="text-lg font-bold text-red-600 mt-2">You are currently spending faster than expected.</p>
                    @else
                        <p class="text-lg font-bold text-green-600 mt-2">You're on track and projected to stay within your budget this month.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="space-y-3 mb-8">
            <h2 class="text-lg font-bold text-gray-900">Main</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Spending Breakdown</h3>
                    @if ($categories_with_totals->isEmpty())
                        <p class="text-sm text-gray-500">No spending recorded for this month yet. Add expenses to see category trends.</p>
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
                        @endphp
                        <div class="space-y-3">
                            @foreach ($categories_with_totals as $category)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-semibold text-gray-800">{{ $category->category }}</p>
                                        <p class="text-sm text-gray-600">
                                            £{{ number_format($category->total, 2) }}
                                            <span class="text-xs text-gray-500">({{ number_format($category->percentage, 0) }}%)</span>
                                        </p>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full {{ $barColors[$loop->index % count($barColors)] }}" style="width: {{ min(100, $category->percentage) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Recent Expenses</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="text-left py-3 px-2 font-semibold">Name</th>
                                    <th class="text-left py-3 px-2 font-semibold">Category</th>
                                    <th class="text-left py-3 px-2 font-semibold">Date</th>
                                    <th class="text-right py-3 px-2 font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($recent_expenses as $expense)
                                    <tr>
                                        <td class="py-3 px-2 text-gray-800">{{ $expense->name }}</td>
                                        <td class="py-3 px-2 text-gray-700">{{ $expense->category }}</td>
                                        <td class="py-3 px-2 text-gray-700">{{ $expense->date }}</td>
                                        <td class="py-3 px-2 text-right text-gray-900 font-semibold">£{{ number_format($expense->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 px-2 text-sm text-gray-500">No expenses yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-3 mb-6">
            <h2 class="text-lg font-bold text-gray-900">Add Transactions</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Add Income</h3>
                    <form method="POST" action="/income" class="space-y-4">
                        @csrf
                        <div>
                            <label for="income_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input id="income_name" name="name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="income_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input id="income_amount" name="amount" type="number" step="0.01" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="income_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input id="income_date" name="date" type="date" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <button type="submit" class="w-full sm:w-auto bg-gray-900 hover:bg-black text-white font-semibold px-4 py-2 rounded-lg border border-gray-900">Add Income</button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Add Expense</h3>
                    <form method="POST" action="/expenses" class="space-y-4">
                        @csrf
                        <div>
                            <label for="expense_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input id="expense_name" name="name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="expense_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input id="expense_amount" name="amount" type="number" step="0.01" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input id="expense_date" name="date" type="date" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <button type="submit" class="w-full sm:w-auto bg-gray-900 hover:bg-black text-white font-semibold px-4 py-2 rounded-lg border border-gray-900">Add Expense</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
