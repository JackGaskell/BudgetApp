<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
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
            $date = Carbon::parse($validated['date']);
            $request->user()->recurringExpenses()->create([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'category' => $validated['category'],
                'day_of_month' => $date->day,
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ]);
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
                $date = Carbon::parse($validated['date']);
                $rule->update([
                    'name' => $validated['name'],
                    'amount' => $validated['amount'],
                    'category' => $validated['category'],
                    'day_of_month' => $date->day,
                ]);
                $ruleSync->syncExpenseRuleToTransactions($rule->fresh());
            }
        } elseif ($wantsRecurring && ! $expense->recurring_expense_id) {
            $expense->update(Arr::only($validated, ['name', 'amount', 'category', 'date']));
            $date = Carbon::parse($validated['date']);
            $rule = $request->user()->recurringExpenses()->create([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'category' => $validated['category'],
                'day_of_month' => $date->day,
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ]);
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
            if ($rule !== null && $rule->user_id === $userId) {
                $canonicalName = trim($rule->name);
                $billingDay = (int) $rule->day_of_month;
            } else {
                $canonicalName = trim($expense->name);
                $billingDay = (int) Carbon::parse($expense->date)->day;
            }

            // Drop rules first so materializeMonth cannot recreate rows on the next page load.
            $this->purgeRecurringExpenseRulesForUserByNameAndDay($userId, $canonicalName, $billingDay);

            Expense::query()->whereKey($expense->id)->where('user_id', $userId)->delete();

            // Duplicates / legacy rows (often null recurring_expense_id), including amount/category drift.
            $this->deleteOrphanExpensesByNameAndDay($userId, $canonicalName, $billingDay);
        });

        return redirect()->back()->with('status', __('Expense deleted successfully.'));
    }

    private function purgeRecurringExpenseRulesForUserByNameAndDay(int $userId, string $canonicalName, int $billingDay): void
    {
        $rules = RecurringExpense::query()
            ->where('user_id', $userId)
            ->where('day_of_month', $billingDay)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();

        foreach ($rules as $rule) {
            $rule->expenses()->delete();
            $rule->delete();
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

    /**
     * @return array<string, mixed>
     */
    private function expenseValidationRules(): array
    {
        $allowedCategories = implode(',', Expense::CATEGORIES);

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
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
