@extends('layouts.budget')

@section('title', __('Edit repeating income'))

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit repeating income') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Changes apply to this repeat and every month already generated from it.') }}</p>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
            <form method="POST" action="{{ route('recurring.income.update', $recurringIncome) }}" class="space-y-4" novalidate>
                @csrf
                @method('PATCH')
                <div>
                    <label for="ri_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input id="ri_name" name="name" type="text" value="{{ old('name', $recurringIncome->name) }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                    <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 @error('amount') border-red-500 @enderror">
                        <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">£</span>
                        <input id="ri_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('amount', $recurringIncome->amount) }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ri_day" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Day of month') }}</label>
                    <input id="ri_day" name="day_of_month" type="number" min="1" max="31" value="{{ old('day_of_month', $recurringIncome->day_of_month) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('day_of_month') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">{{ __('If the month is shorter, the last day of that month is used for each entry.') }}</p>
                    @error('day_of_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Save changes') }}</button>
                    <a href="{{ route('recurring.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
