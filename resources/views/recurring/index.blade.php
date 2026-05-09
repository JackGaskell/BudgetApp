@extends('layouts.budget')

@section('title', __('Recurring'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Recurring payments') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Set up monthly direct debits and income. Each month, matching entries are added to your dashboard and records automatically. You can still edit or delete individual months in Records.') }}</p>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Add monthly expense') }}</h2>
            <form method="POST" action="{{ route('recurring.expenses.store') }}" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label for="re_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input id="re_name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('name') border-red-500 @enderror" placeholder="e.g. Rent, Phone">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="re_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                    <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 @error('amount') border-red-500 @enderror">
                        <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">£</span>
                        <input id="re_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="rec_exp_category" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                    <select id="rec_exp_category" name="category" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 @error('category') border-red-500 @enderror">
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
                    <label for="re_day" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Day of month') }}</label>
                    <input id="re_day" name="day_of_month" type="number" min="1" max="31" value="{{ old('day_of_month', '1') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('day_of_month') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">{{ __('If the month is shorter (e.g. 31st in February), the last day of the month is used.') }}</p>
                    @error('day_of_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="re_starts" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Starts on') }}</label>
                    <input id="re_starts" name="starts_on" type="date" value="{{ old('starts_on', now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('starts_on') border-red-500 @enderror">
                    @error('starts_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="re_ends" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Ends on (optional)') }}</label>
                    <input id="re_ends" name="ends_on" type="date" value="{{ old('ends_on') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('ends_on') border-red-500 @enderror">
                    @error('ends_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Save monthly expense') }}</button>
            </form>

            <h3 class="mb-3 mt-8 text-base font-bold text-gray-900">{{ __('Your monthly expenses') }}</h3>
            @if ($recurring_expenses->isEmpty())
                <p class="text-sm text-gray-500">{{ __('None yet.') }}</p>
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
                            <form method="POST" action="{{ route('recurring.expenses.destroy', $rule) }}" onsubmit="return confirm(@json(__('Remove this monthly rule?')));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-gray-900">{{ __('Add monthly income') }}</h2>
            <form method="POST" action="{{ route('recurring.income.store') }}" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label for="ri_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input id="ri_name" name="income_name" type="text" value="{{ old('income_name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error('income_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                    <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white">
                        <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">£</span>
                        <input id="ri_amount" name="income_amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('income_amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                    </div>
                    @error('income_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_day" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Day of month') }}</label>
                    <input id="ri_day" name="income_day_of_month" type="number" min="1" max="31" value="{{ old('income_day_of_month', '1') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error('income_day_of_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_starts" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Starts on') }}</label>
                    <input id="ri_starts" name="income_starts_on" type="date" value="{{ old('income_starts_on', now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error('income_starts_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_ends" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Ends on (optional)') }}</label>
                    <input id="ri_ends" name="income_ends_on" type="date" value="{{ old('income_ends_on') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error('income_ends_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Save monthly income') }}</button>
            </form>

            <h3 class="mb-3 mt-8 text-base font-bold text-gray-900">{{ __('Your monthly income') }}</h3>
            @if ($recurring_incomes->isEmpty())
                <p class="text-sm text-gray-500">{{ __('None yet.') }}</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($recurring_incomes as $rule)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $rule->name }}</p>
                                <p class="text-xs text-gray-600">@money($rule->amount) · {{ __('Day') }} {{ $rule->day_of_month }}</p>
                            </div>
                            <form method="POST" action="{{ route('recurring.income.destroy', $rule) }}" onsubmit="return confirm(@json(__('Remove this monthly rule?')));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
