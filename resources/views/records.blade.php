@extends('layouts.budget')

@section('title', __('Records'))

@section('content')
    @include('layouts.partials.month-navigation', ['targetRoute' => 'records.index'])

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Your records') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Income and expenses for the month above. “Repeats” marks monthly repeats—edit a row to turn that on or off.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Expenses') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 text-gray-600">
                        <tr>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Name') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Repeats') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Category') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Date') }}</th>
                            <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Amount') }} (£)</th>
                            <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($expenses as $expense)
                            <tr>
                                <td class="px-2 py-3 text-gray-800">{{ $expense->name }}</td>
                                <td class="px-2 py-3 text-gray-600">
                                    @if ($expense->recurring_expense_id)
                                        <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800" title="{{ __('This amount repeats every month') }}">{{ __('Yes') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('—') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-gray-700">{{ $expense->category }}</td>
                                <td class="px-2 py-3">
                                    @if ($expense->date <= $split_date)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">{{ __('Paid') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('Upcoming') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($expense->date)->format('j M Y') }}</td>
                                <td class="px-2 py-3 text-right text-gray-900">@money($expense->amount)</td>
                                <td class="px-2 py-3 text-right">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('expenses.edit', array_merge(['expense' => $expense], \App\Support\ViewMonth::queryParams($view_year, $view_month))) }}" class="inline-flex rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">{{ __('Edit') }}</a>
                                        <button type="button" class="inline-flex rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2" onclick="document.getElementById('delete-expense-{{ $expense->id }}').showModal()">
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                    <dialog id="delete-expense-{{ $expense->id }}" class="w-[calc(100vw-2rem)] max-w-md rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-black/40">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-2 py-6 text-sm text-gray-500">
                                    <p class="font-medium text-gray-800">{{ __('No expenses yet') }}</p>
                                    <p class="mt-1">{{ __('Add an expense from the dashboard to see it here.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Income') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 text-gray-600">
                        <tr>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Name') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Repeats') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-2 py-3 text-left font-semibold">{{ __('Date') }}</th>
                            <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Amount') }} (£)</th>
                            <th scope="col" class="px-2 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($incomes as $income)
                            <tr>
                                <td class="px-2 py-3 text-gray-800">{{ $income->name }}</td>
                                <td class="px-2 py-3 text-gray-600">
                                    @if ($income->recurring_income_id)
                                        <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800" title="{{ __('This amount repeats every month') }}">{{ __('Yes') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('—') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3">
                                    @if ($income->date <= $split_date)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">{{ __('Received') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">{{ __('Scheduled') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($income->date)->format('j M Y') }}</td>
                                <td class="px-2 py-3 text-right text-gray-900">@money($income->amount)</td>
                                <td class="px-2 py-3 text-right">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('income.edit', array_merge(['income' => $income], \App\Support\ViewMonth::queryParams($view_year, $view_month))) }}" class="inline-flex rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">{{ __('Edit') }}</a>
                                        <button type="button" class="inline-flex rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2" onclick="document.getElementById('delete-income-{{ $income->id }}').showModal()">
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                    <dialog id="delete-income-{{ $income->id }}" class="w-[calc(100vw-2rem)] max-w-md rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-black/40">
                                        <form method="POST" action="{{ route('income.destroy', $income) }}" class="p-6">
                                            @csrf
                                            @method('DELETE')
                                            <h3 class="text-lg font-bold text-gray-900">{{ __('Delete income?') }}</h3>
                                            <p class="mt-2 text-sm text-gray-600">{{ __('This will remove “:name” (:amount) from your records.', ['name' => $income->name, 'amount' => \App\Support\Money::format($income->amount)]) }}</p>
                                            <div class="mt-6 flex flex-wrap justify-end gap-2">
                                                <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2" onclick="this.closest('dialog').close()">{{ __('Cancel') }}</button>
                                                <button type="submit" class="rounded-lg border border-red-700 bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">{{ __('Delete') }}</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-2 py-6 text-sm text-gray-500">
                                    <p class="font-medium text-gray-800">{{ __('No income yet') }}</p>
                                    <p class="mt-1">{{ __('Add income from the dashboard to see it here.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
