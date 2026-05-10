{{-- Global add-transaction dialog; parent must use x-data="budgetShell(...)" on <body> --}}
<div
    x-show="addOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="add-transaction-title"
    @keydown.escape.window="if (addOpen) closeAdd()"
>
    <button
        type="button"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-[2px] transition-opacity"
        aria-label="{{ __('Close') }}"
        @click="closeAdd()"
    ></button>

    <div
        x-ref="addTransactionPanel"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        class="relative z-10 flex max-h-[min(90vh,720px)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-gray-200 bg-white shadow-2xl sm:rounded-2xl"
        @click.stop
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
            <div>
                <h2 id="add-transaction-title" class="text-lg font-semibold text-gray-900">{{ __('New transaction') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('All amounts are in pounds sterling (GBP).') }}</p>
            </div>
            <button
                type="button"
                class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900"
                @click="closeAdd()"
            >
                <span class="sr-only">{{ __('Close') }}</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex shrink-0 gap-1 border-b border-gray-100 px-5 py-3 sm:px-6">
            <button
                type="button"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                :class="addTab === 'expense' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="setAddTab('expense')"
            >
                {{ __('Expense') }}
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                :class="addTab === 'income' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="setAddTab('income')"
            >
                {{ __('Income') }}
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 sm:px-6">
            <div x-show="addTab === 'expense'" class="space-y-4">
                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4" novalidate>
                    @csrf
                    <div>
                        <label for="modal_add_expense_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                        <input id="modal_add_expense_name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="modal_add_expense_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                        <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('amount') border-red-500 @enderror">
                            <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600" aria-hidden="true">£</span>
                            <input id="modal_add_expense_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="modal_add_expense_category" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                        <select id="modal_add_expense_category" name="category" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 @error('category') border-red-500 @enderror">
                            <option value="" disabled @selected(! old('category'))>{{ __('Select a category') }}</option>
                            @foreach ($add_modal_expense_categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="modal_add_expense_date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                        <input id="modal_add_expense_date" name="date" type="date" value="{{ old('date', $add_modal_default_date) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('date') border-red-500 @enderror">
                        @error('date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-start gap-3">
                        <input id="modal_add_expense_recurring" name="recurring" type="checkbox" value="1" @checked(old('recurring')) class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="modal_add_expense_recurring" class="text-sm text-gray-700">{{ __('Repeat monthly') }}</label>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 sm:w-auto" @click="closeAdd()">{{ __('Cancel') }}</button>
                        <button type="submit" class="w-full rounded-lg border border-gray-900 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 sm:w-auto">{{ __('Add expense') }}</button>
                    </div>
                </form>
            </div>

            <div x-show="addTab === 'income'" class="space-y-4" x-cloak>
                <form method="POST" action="{{ route('income.store') }}" class="space-y-4" novalidate>
                    @csrf
                    <div>
                        <label for="modal_add_income_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                        <input id="modal_add_income_name" name="income_name" type="text" value="{{ old('income_name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('income_name') border-red-500 @enderror">
                        @error('income_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="modal_add_income_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                        <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-gray-900 focus-within:ring-offset-0 @error('income_amount') border-red-500 @enderror">
                            <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600" aria-hidden="true">£</span>
                            <input id="modal_add_income_amount" name="income_amount" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" value="{{ old('income_amount') }}" required class="min-w-0 flex-1 border-0 px-3 py-2 focus:ring-0">
                        </div>
                        @error('income_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="modal_add_income_date" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                        <input id="modal_add_income_date" name="income_date" type="date" value="{{ old('income_date', $add_modal_default_date) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 @error('income_date') border-red-500 @enderror">
                        @error('income_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-start gap-3">
                        <input id="modal_add_income_recurring" name="income_recurring" type="checkbox" value="1" @checked(old('income_recurring')) class="mt-1 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="modal_add_income_recurring" class="text-sm text-gray-700">{{ __('Repeat monthly') }}</label>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 sm:w-auto" @click="closeAdd()">{{ __('Cancel') }}</button>
                        <button type="submit" class="w-full rounded-lg border border-emerald-600 bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 sm:w-auto">{{ __('Add income') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<button
    type="button"
    class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gray-900 text-white shadow-lg ring-1 ring-black/10 transition hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2 md:hidden"
    @click="openAdd()"
    aria-label="{{ __('Add transaction') }}"
>
    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
</button>
