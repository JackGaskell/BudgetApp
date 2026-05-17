@if (auth()->user()?->hasStudentFeatures())
    @php
        $plan = data_get($funding_snapshot ?? null, 'plan') ?? auth()->user()->studentFundingPlan;
        $snap = $funding_snapshot ?? null;
    @endphp
    <section class="mb-6" aria-label="{{ __('Student loan planning') }}">
        <div class="overflow-hidden rounded-xl border border-indigo-100 bg-white shadow-sm">
            <div class="border-b border-indigo-100 bg-indigo-50/60 px-4 py-3 sm:px-5">
                <h2 class="text-base font-bold text-indigo-950">{{ __('Student loan planning') }}</h2>
                <p class="mt-0.5 text-sm text-indigo-900/80">
                    {{ __('Spread your loan until the next payment or term end. Other income (like a part-time job) is included in your period totals below.') }}
                </p>
            </div>

            @if ($snap)
                <div class="border-b border-indigo-100/80 bg-indigo-50/30 px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-800/70">{{ __('Until next loan / term end') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">
                                {{ \Illuminate\Support\Carbon::parse($snap['end'])->format('j M Y') }}
                                <span class="text-sm font-normal text-gray-600">({{ $snap['days_remaining'] }} {{ $snap['days_remaining'] === 1 ? __('day left') : __('days left') }})</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ __('Spread from this loan') }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900">
                                @money($snap['spread_amount'])
                                <span class="text-sm font-semibold text-gray-600">
                                    / {{ $snap['spread_frequency'] === \App\Models\StudentFundingPlan::FREQUENCY_WEEKLY ? __('week') : __('month') }}
                                </span>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ __(':amount received :date', [
                                    'amount' => \App\Support\Money::format($snap['loan_amount']),
                                    'date' => $snap['start']->format('j M Y'),
                                ]) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-white/80 bg-white/70 px-3 py-2.5">
                            <p class="text-xs text-gray-500">{{ __('Income this period') }}</p>
                            <p class="mt-0.5 text-lg font-bold tabular-nums text-gray-900">@money($snap['income_so_far'])</p>
                            <p class="mt-0.5 text-[10px] text-gray-500">{{ __('Includes loan, wages, and any other income') }}</p>
                        </div>
                        <div class="rounded-lg border border-white/80 bg-white/70 px-3 py-2.5">
                            <p class="text-xs text-gray-500">{{ __('Spent this period') }}</p>
                            <p class="mt-0.5 text-lg font-bold tabular-nums text-gray-900">@money($snap['expenses_so_far'])</p>
                        </div>
                        <div class="rounded-lg border border-white/80 bg-white/70 px-3 py-2.5">
                            <p class="text-xs text-gray-500">{{ __('Net this period') }}</p>
                            <p class="mt-0.5 text-lg font-bold tabular-nums {{ $snap['net_so_far'] >= 0 ? 'text-green-800' : 'text-red-700' }}">@money($snap['net_so_far'])</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-lg px-3 py-2 text-sm {{ $snap['on_loan_pace'] ? 'bg-green-100/80 text-green-900' : 'bg-amber-100/80 text-amber-950' }}">
                        @if ($snap['on_loan_pace'])
                            {{ __('You’re on track with your loan pace so far.') }}
                        @else
                            {{ __('You’re about :amount above the even loan pace for time elapsed.', ['amount' => \App\Support\Money::format($snap['over_loan_pace_by'])]) }}
                        @endif
                    </div>
                </div>
            @endif

            <div class="px-4 py-4 sm:px-5" x-data="{ editing: @json(! $snap) }">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-medium text-gray-900" x-text="editing ? '{{ __('Set up your loan plan') }}' : '{{ __('Loan plan') }}'"></p>
                    @if ($snap)
                        <button
                            type="button"
                            class="text-sm font-medium text-indigo-700 hover:text-indigo-900"
                            @click="editing = !editing"
                            x-text="editing ? '{{ __('Cancel') }}' : '{{ __('Edit') }}'"
                        ></button>
                    @endif
                </div>

                <form
                    method="POST"
                    action="{{ route('student.funding-plan.store') }}"
                    class="mt-4 space-y-4"
                    x-show="editing"
                    x-cloak
                    novalidate
                >
                    @csrf
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
                        <label for="funding_amount" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Loan amount') }} (£)</label>
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
                            <label for="funding_next_payment_on" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Next loan or term end') }}</label>
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
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-lg border border-gray-900 bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2">
                            {{ $snap ? __('Save changes') : __('Save loan plan') }}
                        </button>
                        @if ($snap)
                            <button type="button" class="text-sm font-medium text-gray-600 hover:text-gray-900" @click="editing = false">{{ __('Cancel') }}</button>
                        @endif
                    </div>
                </form>

                @if ($snap)
                    <form method="POST" action="{{ route('student.funding-plan.destroy') }}" class="mt-4 border-t border-gray-100 pt-4" onsubmit="return confirm(@js(__('Remove this loan plan? The linked income entry will be deleted.')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">
                            {{ __('Remove loan plan') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endif
