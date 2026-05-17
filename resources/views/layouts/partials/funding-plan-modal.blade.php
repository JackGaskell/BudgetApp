@php
    $plan = data_get($funding_snapshot ?? null, 'plan') ?? auth()->user()?->studentFundingPlan;
    $snap = $funding_snapshot ?? null;
@endphp
<dialog
    id="funding-plan-modal"
    class="w-[calc(100vw-2rem)] max-w-lg rounded-xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-black/40"
    @if ($errors->hasAny(['name', 'amount', 'received_on', 'next_payment_on', 'spread_frequency']))
        open
    @endif
>
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
        <h2 class="text-lg font-semibold text-gray-900">{{ $snap ? __('Edit loan plan') : __('Track a lump-sum loan') }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Adds the loan as income on the date it arrives and shows how much you can spread until your next payment or term end.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('funding-plan.store') }}" class="max-h-[min(70vh,32rem)] overflow-y-auto px-5 py-4 sm:px-6" novalidate>
        @csrf
        <div class="space-y-4">
            <div>
                <label for="funding_name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                <input
                    id="funding_name"
                    name="name"
                    type="text"
                    value="{{ old('name', $plan?->name ?? __('Student loan')) }}"
                    required
                    maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm @error('name') border-red-500 @enderror"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="funding_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Amount') }} (£)</label>
                <div class="flex overflow-hidden rounded-lg border border-gray-300 bg-white @error('amount') border-red-500 @enderror">
                    <span class="flex shrink-0 items-center border-r border-gray-300 bg-gray-50 px-3 text-sm font-medium text-gray-600">£</span>
                    <input id="funding_amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('amount', $plan?->amount) }}" required class="min-w-0 flex-1 border-0 px-3 py-2 text-sm focus:ring-0">
                </div>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="funding_received_on" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date it arrives') }}</label>
                    <input id="funding_received_on" name="received_on" type="date" value="{{ old('received_on', $plan?->received_on?->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm @error('received_on') border-red-500 @enderror">
                    @error('received_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="funding_next_payment_on" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Next payment or term end') }}</label>
                    <input id="funding_next_payment_on" name="next_payment_on" type="date" value="{{ old('next_payment_on', $plan?->next_payment_on?->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm @error('next_payment_on') border-red-500 @enderror">
                    @error('next_payment_on')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <label for="funding_spread_frequency" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Spread evenly by') }}</label>
                <select id="funding_spread_frequency" name="spread_frequency" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm @error('spread_frequency') border-red-500 @enderror">
                    <option value="weekly" @selected(old('spread_frequency', $plan?->spread_frequency) === 'weekly')>{{ __('Week') }}</option>
                    <option value="monthly" @selected(old('spread_frequency', $plan?->spread_frequency ?? 'monthly') === 'monthly')>{{ __('Month') }}</option>
                </select>
                @error('spread_frequency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2" onclick="document.getElementById('funding-plan-modal').close()">
                {{ __('Cancel') }}
            </button>
            <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                {{ $snap ? __('Save changes') : __('Save') }}
            </button>
        </div>
    </form>

    @if ($snap)
        <form method="POST" action="{{ route('funding-plan.destroy') }}" class="border-t border-gray-100 px-5 py-4 sm:px-6" onsubmit="return confirm(@js(__('Remove this loan plan? The linked income entry will be deleted.')))">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">
                {{ __('Remove loan plan') }}
            </button>
        </form>
    @endif
</dialog>
