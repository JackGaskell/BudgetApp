<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Records</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="w-full bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <p class="text-lg font-bold text-gray-900">SpendSense</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Dashboard</a>
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

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Your Records</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your saved income and expenses.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Expenses</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="text-left py-3 px-2 font-semibold">Name</th>
                                <th class="text-left py-3 px-2 font-semibold">Category</th>
                                <th class="text-left py-3 px-2 font-semibold">Status</th>
                                <th class="text-left py-3 px-2 font-semibold">Date</th>
                                <th class="text-right py-3 px-2 font-semibold">Amount (£)</th>
                                <th class="text-right py-3 px-2 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td class="py-3 px-2 text-gray-800">{{ $expense->name }}</td>
                                    <td class="py-3 px-2 text-gray-700">{{ $expense->category }}</td>
                                    <td class="py-3 px-2">
                                        @if ($expense->date <= now()->toDateString())
                                            <span class="inline-flex text-xs font-medium bg-green-100 text-green-700 px-2 py-1 rounded-full">Paid</span>
                                        @else
                                            <span class="inline-flex text-xs font-medium bg-amber-100 text-amber-700 px-2 py-1 rounded-full">Upcoming</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-gray-700">{{ $expense->date }}</td>
                                    <td class="py-3 px-2 text-right text-gray-900">@money($expense->amount)</td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-2.5 py-1 rounded-lg">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 px-2 text-sm text-gray-500">No expenses yet — add one from the dashboard.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Incomes</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="text-left py-3 px-2 font-semibold">Name</th>
                                <th class="text-left py-3 px-2 font-semibold">Status</th>
                                <th class="text-left py-3 px-2 font-semibold">Date</th>
                                <th class="text-right py-3 px-2 font-semibold">Amount (£)</th>
                                <th class="text-right py-3 px-2 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($incomes as $income)
                                <tr>
                                    <td class="py-3 px-2 text-gray-800">{{ $income->name }}</td>
                                    <td class="py-3 px-2">
                                        @if ($income->date <= now()->toDateString())
                                            <span class="inline-flex text-xs font-medium bg-green-100 text-green-700 px-2 py-1 rounded-full">Received</span>
                                        @else
                                            <span class="inline-flex text-xs font-medium bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Scheduled</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-gray-700">{{ $income->date }}</td>
                                    <td class="py-3 px-2 text-right text-gray-900">@money($income->amount)</td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('income.destroy', $income) }}" onsubmit="return confirm('Delete this income?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-2.5 py-1 rounded-lg">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 px-2 text-sm text-gray-500">No income yet — add one from the dashboard.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
