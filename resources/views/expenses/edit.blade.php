@extends('layouts.budget')

@section('title', __('Edit expense'))

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit expense') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Update the details below. Amounts are in pounds sterling (GBP).') }}</p>
            @if ($expense->recurring_expense_id)
                <p class="mt-2 text-sm text-gray-600">{{ __('With “Repeat monthly” on, saving updates every month this payment appears in your records.') }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-4" novalidate>
                @csrf
                @method('PATCH')
                @if ($return_year !== null && $return_year !== '' && $return_month !== null && $return_month !== '')
                    <input type="hidden" name="return_year" value="{{ $return_year }}">
                    <input type="hidden" name="return_month" value="{{ $return_month }}">
                @endif
                <div>
                    <label for="expense_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input id="expense_name" name="name" type="text" value="{{ old('name', $expense->name) }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                    <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('amount') border-red-500 @enderror">
                        <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600" aria-hidden="true">£</span>
                        <input id="expense_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('amount', $expense->amount) }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_category" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                    <select id="expense_category" name="category" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 @error('category') border-red-500 @enderror">
                        @foreach ($expense_categories as $category)
                            <option value="{{ $category }}" @selected(old('category', $expense->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expense_date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                    <input id="expense_date" name="date" type="date" value="{{ old('date', $expense->date) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-start gap-3 border-t border-gray-100 pt-4">
                    <input id="expense_recurring" name="recurring" type="checkbox" value="1" @checked(old('recurring', (bool) $expense->recurring_expense_id)) class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                    <label for="expense_recurring" class="text-sm text-gray-700">{{ __('Repeat monthly on this calendar day') }}</label>
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Save changes') }}</button>
                    <a href="{{ $return_year !== null && $return_year !== '' && $return_month !== null && $return_month !== '' ? route('records.index', ['year' => $return_year, 'month' => $return_month]) : route('records.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
