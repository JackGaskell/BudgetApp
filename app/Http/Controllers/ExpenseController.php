<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): JsonResponse
    {
        $expenses = auth()->user()
            ->expenses()
            ->latest('date')
            ->get();

        return response()->json($expenses);
    }

    public function edit(Request $request, Expense $expense): View
    {
        $this->authorizeExpense($request, $expense);

        $expense->load('recurringExpense');

        return view('expenses.edit', [
            'expense' => $expense,
            'expense_categories' => Expense::CATEGORIES,
            'return_year' => $request->query('year'),
            'return_month' => $request->query('month'),
        ]);
    }

    public function store(Request $request, RecurringMaterializer $materializer): RedirectResponse
    {
        $validated = $request->validate(array_merge(
            $this->expenseValidationRules(),
            ['recurring' => ['sometimes', 'boolean']]
        ));

        if ($request->boolean('recurring')) {
            $request->validate(['recurring_frequency' => ['required', 'in:monthly,weekly']]);
            $date = Carbon::parse($validated['date']);
            $frequency = $this->resolveRecurringExpenseFrequency($request);
            $schedule = $this->recurringExpenseScheduleFields($date, $frequency);
            $request->user()->recurringExpenses()->create(array_merge([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'category' => $validated['category'],
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ], $schedule));
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());

            return redirect()->back()->with('status', __('Expense added successfully.'));
        }

        $request->user()->expenses()->create($validated);

        return redirect()->back()->with('status', __('Expense added successfully.'));
    }

    public function update(Request $request, Expense $expense, RecurringMaterializer $materializer, RecurringRuleSync $ruleSync): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);

        $validated = $request->validate(array_merge(
            $this->expenseValidationRules(),
            [
                'return_year' => ['nullable', 'integer'],
                'return_month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'recurring' => ['sometimes', 'boolean'],
                'recurring_frequency' => ['nullable', 'in:monthly,weekly'],
            ]
        ));

        $wantsRecurring = $request->boolean('recurring');
        $hadRecurring = $expense->recurring_expense_id !== null;

        if ($hadRecurring && ! $wantsRecurring) {
            $rule = $expense->recurringExpense;
            if ($rule && $rule->user_id === $request->user()->id) {
                DB::transaction(function () use ($rule, $expense): void {
                    $rule->expenses()->whereKeyNot($expense->id)->delete();
                    $rule->delete();
                });
            }
            $expense->refresh();
        }

        if ($wantsRecurring && $expense->recurring_expense_id) {
            $rule = $expense->recurringExpense;
            if ($rule !== null) {
                if ($request->has('recurring_frequency')) {
                    $request->validate(['recurring_frequency' => ['required', 'in:monthly,weekly']]);
                }
                $date = Carbon::parse($validated['date']);
                $frequency = $this->resolveRecurringExpenseFrequency($request, $rule->frequency);
                $schedule = $this->recurringExpenseScheduleFields($date, $frequency);
                $beforeFreq = $rule->frequency;
                $beforeDom = (int) $rule->day_of_month;
                $beforeDow = $rule->day_of_week !== null ? (int) $rule->day_of_week : null;
                $rule->update(array_merge(
                    [
                        'name' => $validated['name'],
                        'amount' => $validated['amount'],
                        'category' => $validated['category'],
                    ],
                    $schedule
                ));
                $rule->refresh();
                $scheduleChanged = $beforeFreq !== $rule->frequency
                    || ($rule->isMonthly() && $beforeDom !== (int) $rule->day_of_month)
                    || ($rule->isWeekly() && $beforeDow !== (int) $rule->day_of_week);

                if ($scheduleChanged) {
                    $rule->expenses()->delete();
                    $materializer->materializeMonth($request->user(), $date->year, $date->month);
                    $materializer->materializeUpcomingMonths($request->user());
                } else {
                    $ruleSync->syncExpenseRuleToTransactions($rule->fresh());
                }
            }
        } elseif ($wantsRecurring && ! $expense->recurring_expense_id) {
            $request->validate(['recurring_frequency' => ['required', 'in:monthly,weekly']]);
            $expense->update(Arr::only($validated, ['name', 'amount', 'category', 'date']));
            $date = Carbon::parse($validated['date']);
            $frequency = $this->resolveRecurringExpenseFrequency($request);
            $schedule = $this->recurringExpenseScheduleFields($date, $frequency);
            $rule = $request->user()->recurringExpenses()->create(array_merge([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'category' => $validated['category'],
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ], $schedule));
            $expense->update(['recurring_expense_id' => $rule->id]);
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());
        } else {
            $expense->update(Arr::only($validated, ['name', 'amount', 'category', 'date']));
        }

        return $this->redirectToRecordsAfterEdit($request)->with('status', __('Expense updated successfully.'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);

        DB::transaction(function () use ($request, $expense): void {
            $userId = $request->user()->id;
            $rule = $expense->recurringExpense;
            $canonicalName = $rule !== null && $rule->user_id === $userId
                ? trim($rule->name)
                : trim($expense->name);

            if ($rule !== null && $rule->user_id === $userId) {
                $this->purgeRecurringExpenseRulesMatchingSchedule($userId, $canonicalName, $rule);
            } else {
                $this->purgeRecurringExpenseRulesForExpenseWithNoRule($userId, $canonicalName, $expense);
            }

            Expense::query()->whereKey($expense->id)->where('user_id', $userId)->delete();

            $this->deleteOrphanExpensesByNameAndDay($userId, $canonicalName, (int) Carbon::parse($expense->date)->day);
            $this->deleteOrphanExpensesByNameAndWeekday(
                $userId,
                $canonicalName,
                (int) Carbon::parse($expense->date)->dayOfWeek
            );
        });

        return redirect()->back()->with('status', __('Expense deleted successfully.'));
    }

    private function purgeRecurringExpenseRulesMatchingSchedule(int $userId, string $canonicalName, RecurringExpense $match): void
    {
        $query = RecurringExpense::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->where('frequency', $match->frequency);

        if ($match->isWeekly()) {
            $query->where('day_of_week', $match->day_of_week);
        } else {
            $query->where('day_of_month', $match->day_of_month);
        }

        foreach ($query->get() as $r) {
            $r->expenses()->delete();
            $r->delete();
        }
    }

    /**
     * When deleting a one-off row, purge rules that could have produced duplicates with the same label and schedule hints.
     */
    private function purgeRecurringExpenseRulesForExpenseWithNoRule(int $userId, string $canonicalName, Expense $expense): void
    {
        $date = Carbon::parse($expense->date);
        $monthly = RecurringExpense::query()
            ->where('user_id', $userId)
            ->where('frequency', RecurringExpense::FREQUENCY_MONTHLY)
            ->where('day_of_month', $date->day)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();
        foreach ($monthly as $r) {
            $r->expenses()->delete();
            $r->delete();
        }
        $weekly = RecurringExpense::query()
            ->where('user_id', $userId)
            ->where('frequency', RecurringExpense::FREQUENCY_WEEKLY)
            ->where('day_of_week', $date->dayOfWeek)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();
        foreach ($weekly as $r) {
            $r->expenses()->delete();
            $r->delete();
        }
    }

    private function deleteOrphanExpensesByNameAndDay(int $userId, string $canonicalName, int $billingDay): void
    {
        Expense::query()
            ->where('user_id', $userId)
            ->whereNull('recurring_expense_id')
            ->whereDay('date', $billingDay)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->delete();
    }

    private function deleteOrphanExpensesByNameAndWeekday(int $userId, string $canonicalName, int $dayOfWeek): void
    {
        Expense::query()
            ->where('user_id', $userId)
            ->whereNull('recurring_expense_id')
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get()
            ->each(function (Expense $expense) use ($dayOfWeek): void {
                if ((int) Carbon::parse($expense->date)->dayOfWeek === $dayOfWeek) {
                    $expense->delete();
                }
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function recurringExpenseScheduleFields(Carbon $date, string $frequency): array
    {
        if ($frequency === RecurringExpense::FREQUENCY_WEEKLY) {
            return [
                'frequency' => RecurringExpense::FREQUENCY_WEEKLY,
                'day_of_month' => $date->day,
                'day_of_week' => $date->dayOfWeek,
            ];
        }

        return [
            'frequency' => RecurringExpense::FREQUENCY_MONTHLY,
            'day_of_month' => $date->day,
            'day_of_week' => null,
        ];
    }

    private function resolveRecurringExpenseFrequency(Request $request, ?string $default = null): string
    {
        $fallback = $default ?? RecurringExpense::FREQUENCY_MONTHLY;
        $v = $request->input('recurring_frequency');

        return in_array($v, [RecurringExpense::FREQUENCY_MONTHLY, RecurringExpense::FREQUENCY_WEEKLY], true)
            ? $v
            : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseValidationRules(): array
    {
        $allowedCategories = implode(',', Expense::CATEGORIES);

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:'.Money::MAX_AMOUNT],
            'category' => ['required', 'string', 'in:'.$allowedCategories],
            'date' => ['required', 'date'],
        ];
    }

    private function authorizeExpense(Request $request, Expense $expense): void
    {
        if ($expense->user_id !== $request->user()->id) {
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
