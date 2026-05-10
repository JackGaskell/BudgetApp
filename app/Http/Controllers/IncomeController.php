<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\RecurringIncome;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function store(Request $request, RecurringMaterializer $materializer): RedirectResponse
    {
        $validated = $request->validate([
            'income_name' => ['required', 'string', 'max:255'],
            'income_amount' => ['required', 'numeric', 'min:0', 'max:'.Money::MAX_AMOUNT],
            'income_date' => ['required', 'date'],
            'income_recurring' => ['sometimes', 'boolean'],
        ], [], [
            'income_name' => __('Income name'),
            'income_amount' => __('Income amount'),
            'income_date' => __('Income date'),
        ]);

        if ($request->boolean('income_recurring')) {
            $request->validate([
                'income_recurring_frequency' => ['required', 'in:monthly,weekly'],
            ]);
            $date = Carbon::parse($validated['income_date']);
            $frequency = $this->resolveRecurringIncomeFrequency($request);
            $schedule = $this->recurringIncomeScheduleFields($date, $frequency);
            $request->user()->recurringIncomes()->create(array_merge([
                'name' => $validated['income_name'],
                'amount' => $validated['income_amount'],
                'starts_on' => $validated['income_date'],
                'ends_on' => null,
            ], $schedule));
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());

            return redirect()->back()->with('status', __('Income added successfully.'));
        }

        $request->user()->incomes()->create([
            'name' => $validated['income_name'],
            'amount' => $validated['income_amount'],
            'date' => $validated['income_date'],
        ]);

        return redirect()->back()->with('status', __('Income added successfully.'));
    }

    public function edit(Request $request, Income $income): View
    {
        $this->authorizeIncome($request, $income);

        $income->load('recurringIncome');

        return view('income.edit', [
            'income' => $income,
            'return_year' => $request->query('year'),
            'return_month' => $request->query('month'),
        ]);
    }

    public function update(Request $request, Income $income, RecurringMaterializer $materializer, RecurringRuleSync $ruleSync): RedirectResponse
    {
        $this->authorizeIncome($request, $income);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:'.Money::MAX_AMOUNT],
            'date' => ['required', 'date'],
            'return_year' => ['nullable', 'integer'],
            'return_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'income_recurring' => ['sometimes', 'boolean'],
            'income_recurring_frequency' => ['nullable', 'in:monthly,weekly'],
        ]);

        $wantsRecurring = $request->boolean('income_recurring');
        $hadRecurring = $income->recurring_income_id !== null;

        if ($hadRecurring && ! $wantsRecurring) {
            $rule = $income->recurringIncome;
            if ($rule && $rule->user_id === $request->user()->id) {
                DB::transaction(function () use ($rule, $income): void {
                    $rule->incomes()->whereKeyNot($income->id)->delete();
                    $rule->delete();
                });
            }
            $income->refresh();
        }

        if ($wantsRecurring && $income->recurring_income_id) {
            $rule = $income->recurringIncome;
            if ($rule !== null) {
                if ($request->has('income_recurring_frequency')) {
                    $request->validate(['income_recurring_frequency' => ['required', 'in:monthly,weekly']]);
                }
                $date = Carbon::parse($validated['date']);
                $frequency = $this->resolveRecurringIncomeFrequency($request, $rule->frequency);
                $schedule = $this->recurringIncomeScheduleFields($date, $frequency);
                $beforeFreq = $rule->frequency;
                $beforeDom = (int) $rule->day_of_month;
                $beforeDow = $rule->day_of_week !== null ? (int) $rule->day_of_week : null;
                $rule->update(array_merge(
                    [
                        'name' => $validated['name'],
                        'amount' => $validated['amount'],
                    ],
                    $schedule
                ));
                $rule->refresh();
                $scheduleChanged = $beforeFreq !== $rule->frequency
                    || ($rule->isMonthly() && $beforeDom !== (int) $rule->day_of_month)
                    || ($rule->isWeekly() && $beforeDow !== (int) $rule->day_of_week);

                if ($scheduleChanged) {
                    $rule->incomes()->delete();
                    $materializer->materializeMonth($request->user(), $date->year, $date->month);
                    $materializer->materializeUpcomingMonths($request->user());
                } else {
                    $ruleSync->syncIncomeRuleToTransactions($rule->fresh());
                }
            }
        } elseif ($wantsRecurring && ! $income->recurring_income_id) {
            $request->validate(['income_recurring_frequency' => ['required', 'in:monthly,weekly']]);
            $income->update([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
            ]);
            $date = Carbon::parse($validated['date']);
            $frequency = $this->resolveRecurringIncomeFrequency($request);
            $schedule = $this->recurringIncomeScheduleFields($date, $frequency);
            $rule = $request->user()->recurringIncomes()->create(array_merge([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ], $schedule));
            $income->update(['recurring_income_id' => $rule->id]);
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());
        } else {
            $income->update([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
            ]);
        }

        return $this->redirectToRecordsAfterEdit($request)->with('status', __('Income updated successfully.'));
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        $this->authorizeIncome($request, $income);

        DB::transaction(function () use ($request, $income): void {
            $userId = $request->user()->id;
            $rule = $income->recurringIncome;
            $canonicalName = $rule !== null && $rule->user_id === $userId
                ? trim($rule->name)
                : trim($income->name);

            if ($rule !== null && $rule->user_id === $userId) {
                $this->purgeRecurringIncomeRulesMatchingSchedule($userId, $canonicalName, $rule);
            } else {
                $this->purgeRecurringIncomeRulesForIncomeWithNoRule($userId, $canonicalName, $income);
            }

            Income::query()->whereKey($income->id)->where('user_id', $userId)->delete();

            $this->deleteOrphanIncomesByNameAndDay($userId, $canonicalName, (int) Carbon::parse($income->date)->day);
            $this->deleteOrphanIncomesByNameAndWeekday(
                $userId,
                $canonicalName,
                (int) Carbon::parse($income->date)->dayOfWeek
            );
        });

        return redirect()->back()->with('status', __('Income deleted successfully.'));
    }

    private function purgeRecurringIncomeRulesMatchingSchedule(int $userId, string $canonicalName, RecurringIncome $match): void
    {
        $query = RecurringIncome::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->where('frequency', $match->frequency);

        if ($match->isWeekly()) {
            $query->where('day_of_week', $match->day_of_week);
        } else {
            $query->where('day_of_month', $match->day_of_month);
        }

        foreach ($query->get() as $r) {
            $r->incomes()->delete();
            $r->delete();
        }
    }

    private function purgeRecurringIncomeRulesForIncomeWithNoRule(int $userId, string $canonicalName, Income $income): void
    {
        $date = Carbon::parse($income->date);
        $monthly = RecurringIncome::query()
            ->where('user_id', $userId)
            ->where('frequency', RecurringIncome::FREQUENCY_MONTHLY)
            ->where('day_of_month', $date->day)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();
        foreach ($monthly as $r) {
            $r->incomes()->delete();
            $r->delete();
        }
        $weekly = RecurringIncome::query()
            ->where('user_id', $userId)
            ->where('frequency', RecurringIncome::FREQUENCY_WEEKLY)
            ->where('day_of_week', $date->dayOfWeek)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();
        foreach ($weekly as $r) {
            $r->incomes()->delete();
            $r->delete();
        }
    }

    private function deleteOrphanIncomesByNameAndDay(int $userId, string $canonicalName, int $billingDay): void
    {
        Income::query()
            ->where('user_id', $userId)
            ->whereNull('recurring_income_id')
            ->whereDay('date', $billingDay)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->delete();
    }

    private function deleteOrphanIncomesByNameAndWeekday(int $userId, string $canonicalName, int $dayOfWeek): void
    {
        Income::query()
            ->where('user_id', $userId)
            ->whereNull('recurring_income_id')
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get()
            ->each(function (Income $income) use ($dayOfWeek): void {
                if ((int) Carbon::parse($income->date)->dayOfWeek === $dayOfWeek) {
                    $income->delete();
                }
            });
    }

    private function recurringIncomeScheduleFields(Carbon $date, string $frequency): array
    {
        if ($frequency === RecurringIncome::FREQUENCY_WEEKLY) {
            return [
                'frequency' => RecurringIncome::FREQUENCY_WEEKLY,
                'day_of_month' => $date->day,
                'day_of_week' => $date->dayOfWeek,
            ];
        }

        return [
            'frequency' => RecurringIncome::FREQUENCY_MONTHLY,
            'day_of_month' => $date->day,
            'day_of_week' => null,
        ];
    }

    private function resolveRecurringIncomeFrequency(Request $request, ?string $default = null): string
    {
        $fallback = $default ?? RecurringIncome::FREQUENCY_MONTHLY;
        $v = $request->input('income_recurring_frequency');

        return in_array($v, [RecurringIncome::FREQUENCY_MONTHLY, RecurringIncome::FREQUENCY_WEEKLY], true)
            ? $v
            : $fallback;
    }

    private function authorizeIncome(Request $request, Income $income): void
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function redirectToRecordsAfterEdit(Request $request): RedirectResponse
    {
        $year = $request->input('return_year');
        $month = $request->input('return_month');
        if ($year !== null && $year !== '' && $month !== null && $month !== '') {
            return redirect()->route('records.index', [
                'year' => (int) $year,
                'month' => (int) $month,
            ]);
        }

        return redirect()->route('records.index');
    }
}
