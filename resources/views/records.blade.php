@extends('layouts.budget')

@section('title', __('Records'))

@section('content')
    @include('layouts.partials.month-navigation', ['targetRoute' => 'records.index'])

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Your records') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Income and expenses for the month above. Use the Repeats column to see which lines repeat every month.') }}</p>
        </div>
    </div>

    @php
        $editExpenseIcon = '<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>';
        $deleteIcon = '<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>';
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-100 bg-white p-3 shadow-sm sm:p-4">
            <h2 class="mb-3 text-lg font-bold text-gray-900 sm:mb-4">{{ __('Expenses') }}</h2>

            @if ($expenses->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-sm text-gray-500">
                    <p class="font-medium text-gray-800">{{ __('No expenses yet') }}</p>
                    <p class="mt-1">{{ __('Add an expense from the dashboard to see it here.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto -mx-3 sm:mx-0">
                    <table class="w-full min-w-0 table-fixed text-xs sm:text-sm">
                        <thead class="border-b border-gray-200 text-gray-600">
                            <tr>
                                <th scope="col" class="w-[26%] py-2 pl-0 pr-1 text-left font-semibold sm:w-[20%] sm:py-3 sm:pr-2">{{ __('Name') }}</th>
                                <th scope="col" class="w-[11%] py-2 pl-1 pr-1 text-center font-semibold sm:w-[9%] sm:py-3 sm:px-2">{{ __('Repeats') }}</th>
                                <th scope="col" class="hidden py-2 text-left font-semibold sm:table-cell sm:w-[17%] sm:py-3 sm:px-2">{{ __('Category') }}</th>
                                <th scope="col" class="w-[12%] py-2 pl-1 pr-1 text-left font-semibold sm:w-[10%] sm:py-3 sm:px-2">{{ __('Status') }}</th>
                                <th scope="col" class="w-[14%] py-2 pl-1 pr-1 text-left font-semibold sm:w-[11%] sm:py-3 sm:px-2">{{ __('Date') }}</th>
                                <th scope="col" class="w-[14%] py-2 pl-1 pr-0 text-right font-semibold tabular-nums sm:w-[12%] sm:py-3 sm:px-2">{{ __('Amount') }}</th>
                                <th scope="col" class="w-[23%] py-2 pl-1 pr-0 text-right font-semibold sm:w-[11%] sm:py-3 sm:pl-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($expenses as $expense)
                                <tr>
                                    <td class="py-2 pl-0 pr-1 align-top sm:py-3 sm:pr-2">
                                        <span class="line-clamp-2 font-medium text-gray-900">{{ $expense->name }}</span>
                                    </td>
                                    <td class="py-2 pl-1 pr-1 text-center align-top sm:py-3 sm:px-2">
                                        @if ($expense->recurring_expense_id)
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-medium tabular-nums text-violet-800 sm:min-w-[2.5rem] sm:text-xs" title="{{ __('Repeats every month') }}">{{ __('Yes') }}</span>
                                        @else
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium tabular-nums text-gray-700 sm:min-w-[2.5rem] sm:text-xs">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="hidden max-w-0 truncate py-2 align-top text-gray-700 sm:table-cell sm:max-w-none sm:py-3 sm:px-2" title="{{ $expense->category }}">{{ $expense->category }}</td>
                                    <td class="py-2 pl-1 pr-1 align-top sm:py-3 sm:px-2">
                                        @if ($expense->date <= $split_date)
                                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 sm:px-2 sm:py-1 sm:text-xs">{{ __('Paid') }}</span>
                                        @else
                                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800 sm:px-2 sm:py-1 sm:text-xs">{{ __('Upcoming') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap py-2 pl-1 pr-1 align-top text-gray-700 sm:py-3 sm:px-2">
                                        {{ \Illuminate\Support\Carbon::parse($expense->date)->format('j M Y') }}
                                    </td>
                                    <td class="py-2 pl-1 pr-0 text-right align-top font-medium tabular-nums text-gray-900 sm:py-3 sm:px-2">@money($expense->amount)</td>
                                    <td class="py-2 pl-1 pr-0 text-right align-top sm:py-3 sm:pl-2">
                                        <div class="inline-flex items-center justify-end gap-0.5 sm:gap-1">
                                            <a href="{{ route('expenses.edit', array_merge(['expense' => $expense], \App\Support\ViewMonth::queryParams($view_year, $view_month))) }}" class="inline-flex items-center justify-center rounded-md p-1.5 text-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-1" title="{{ __('Edit') }}">
                                                {!! $editExpenseIcon !!}
                                                <span class="sr-only">{{ __('Edit') }}</span>
                                            </a>
                                            <button type="button" class="inline-flex items-center justify-center rounded-md p-1.5 text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-1" title="{{ __('Delete') }}" onclick="document.getElementById('delete-expense-{{ $expense->id }}').showModal()">
                                                {!! $deleteIcon !!}
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach ($expenses as $expense)
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
                @endforeach
            @endif
        </section>

        <section class="rounded-xl border border-gray-100 bg-white p-3 shadow-sm sm:p-4">
            <h2 class="mb-3 text-lg font-bold text-gray-900 sm:mb-4">{{ __('Income') }}</h2>

            @if ($incomes->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50/50 px-4 py-6 text-sm text-gray-500">
                    <p class="font-medium text-gray-800">{{ __('No income yet') }}</p>
                    <p class="mt-1">{{ __('Add income from the dashboard to see it here.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto -mx-3 sm:mx-0">
                    <table class="w-full min-w-0 table-fixed text-xs sm:text-sm">
                        <thead class="border-b border-gray-200 text-gray-600">
                            <tr>
                                <th scope="col" class="w-[30%] py-2 pl-0 pr-1 text-left font-semibold sm:w-[26%] sm:py-3 sm:pr-2">{{ __('Name') }}</th>
                                <th scope="col" class="w-[12%] py-2 pl-1 pr-1 text-center font-semibold sm:w-[10%] sm:py-3 sm:px-2">{{ __('Repeats') }}</th>
                                <th scope="col" class="w-[14%] py-2 pl-1 pr-1 text-left font-semibold sm:w-[12%] sm:py-3 sm:px-2">{{ __('Status') }}</th>
                                <th scope="col" class="w-[16%] py-2 pl-1 pr-1 text-left font-semibold sm:w-[13%] sm:py-3 sm:px-2">{{ __('Date') }}</th>
                                <th scope="col" class="w-[14%] py-2 pl-1 pr-0 text-right font-semibold tabular-nums sm:w-[13%] sm:py-3 sm:px-2">{{ __('Amount') }}</th>
                                <th scope="col" class="w-[14%] py-2 pl-1 pr-0 text-right font-semibold sm:w-[12%] sm:py-3 sm:pl-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($incomes as $income)
                                <tr>
                                    <td class="py-2 pl-0 pr-1 align-top sm:py-3 sm:pr-2">
                                        <span class="line-clamp-2 font-medium text-gray-900">{{ $income->name }}</span>
                                    </td>
                                    <td class="py-2 pl-1 pr-1 text-center align-top sm:py-3 sm:px-2">
                                        @if ($income->recurring_income_id)
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-medium tabular-nums text-violet-800 sm:min-w-[2.5rem] sm:text-xs" title="{{ __('Repeats every month') }}">{{ __('Yes') }}</span>
                                        @else
                                            <span class="inline-flex min-w-[2.25rem] justify-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium tabular-nums text-gray-700 sm:min-w-[2.5rem] sm:text-xs">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pl-1 pr-1 align-top sm:py-3 sm:px-2">
                                        @if ($income->date <= $split_date)
                                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 sm:px-2 sm:py-1 sm:text-xs">{{ __('Received') }}</span>
                                        @else
                                            <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-800 sm:px-2 sm:py-1 sm:text-xs">{{ __('Scheduled') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap py-2 pl-1 pr-1 align-top text-gray-700 sm:py-3 sm:px-2">
                                        {{ \Illuminate\Support\Carbon::parse($income->date)->format('j M Y') }}
                                    </td>
                                    <td class="py-2 pl-1 pr-0 text-right align-top font-medium tabular-nums text-gray-900 sm:py-3 sm:px-2">@money($income->amount)</td>
                                    <td class="py-2 pl-1 pr-0 text-right align-top sm:py-3 sm:pl-2">
                                        <div class="inline-flex items-center justify-end gap-0.5 sm:gap-1">
                                            <a href="{{ route('income.edit', array_merge(['income' => $income], \App\Support\ViewMonth::queryParams($view_year, $view_month))) }}" class="inline-flex items-center justify-center rounded-md p-1.5 text-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-1" title="{{ __('Edit') }}">
                                                {!! $editExpenseIcon !!}
                                                <span class="sr-only">{{ __('Edit') }}</span>
                                            </a>
                                            <button type="button" class="inline-flex items-center justify-center rounded-md p-1.5 text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-1" title="{{ __('Delete') }}" onclick="document.getElementById('delete-income-{{ $income->id }}').showModal()">
                                                {!! $deleteIcon !!}
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach ($incomes as $income)
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
                @endforeach
            @endif
        </section>
    </div>
@endsection
