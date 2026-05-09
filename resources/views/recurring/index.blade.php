@extends('layouts.budget')

@section('title', __('Recurring'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Monthly repeats') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Add income or expenses on the dashboard and tick “Repeat every month”. Entries are created automatically each month on the same calendar day (or the last day of shorter months). Remove a repeat below to stop future months—past rows stay in Records unless you delete them.') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Repeating expenses') }}</h2>
            @if ($recurring_expenses->isEmpty())
                <p class="text-sm text-gray-500">{{ __('None yet. Use the expense form on the Dashboard and tick repeat.') }}</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($recurring_expenses as $rule)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $rule->name }}</p>
                                <p class="text-xs text-gray-600">
                                    @money($rule->amount) · {{ $rule->category }} · {{ __('Day') }} {{ $rule->day_of_month }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('recurring.expenses.destroy', $rule) }}" onsubmit="return confirm(@json(__('Stop this repeat? Future months will not be added automatically.')));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">{{ __('Stop repeating') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Repeating income') }}</h2>
            @if ($recurring_incomes->isEmpty())
                <p class="text-sm text-gray-500">{{ __('None yet. Use the income form on the Dashboard and tick repeat.') }}</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($recurring_incomes as $rule)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $rule->name }}</p>
                                <p class="text-xs text-gray-600">@money($rule->amount) · {{ __('Day') }} {{ $rule->day_of_month }}</p>
                            </div>
                            <form method="POST" action="{{ route('recurring.income.destroy', $rule) }}" onsubmit="return confirm(@json(__('Stop this repeat? Future months will not be added automatically.')));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">{{ __('Stop repeating') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
