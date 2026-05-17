@if (auth()->user()?->hasStudentFeatures())
    @if ($funding_snapshot ?? null)
        @php($snap = $funding_snapshot)
        <button
            type="button"
            class="rounded-xl border border-violet-100 bg-violet-50 p-4 text-left shadow-sm transition hover:border-violet-200 hover:bg-violet-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2"
            onclick="document.getElementById('funding-plan-modal').showModal()"
        >
            <p class="text-sm text-gray-600">{{ __('Student loan') }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">
                @money($snap['spread_amount'])
                <span class="text-lg font-semibold text-gray-600">/ {{ $snap['spread_frequency'] === \App\Models\StudentFundingPlan::FREQUENCY_WEEKLY ? __('week') : __('month') }}</span>
            </p>
            <p class="mt-2 text-sm text-gray-700">
                {{ __(':days until next payment · net this period :net', [
                    'days' => $snap['days_remaining'].' '.($snap['days_remaining'] === 1 ? __('day') : __('days')),
                    'net' => \App\Support\Money::format($snap['net_so_far']),
                ]) }}
            </p>
            <p class="mt-1 text-xs {{ $snap['on_loan_pace'] ? 'text-green-800' : 'text-amber-900' }}">
                @if ($snap['on_loan_pace'])
                    {{ __('On track with loan pace') }}
                @else
                    {{ __('Above even loan pace for time elapsed') }}
                @endif
            </p>
            <p class="mt-2 text-xs font-medium text-violet-800">{{ __('Tap to view or edit') }}</p>
        </button>
    @else
        <button
            type="button"
            class="rounded-xl border border-dashed border-violet-200 bg-violet-50/50 p-4 text-left shadow-sm transition hover:border-violet-300 hover:bg-violet-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2"
            onclick="document.getElementById('funding-plan-modal').showModal()"
        >
            <p class="text-sm text-gray-600">{{ __('Student loan') }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">{{ __('Add your loan') }}</p>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Spread payments until your next loan or term end. Counts alongside wages and other income.') }}
            </p>
            <p class="mt-2 text-xs font-medium text-violet-800">{{ __('Tap to set up') }}</p>
        </button>
    @endif
@endif
