<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

            return redirect()->back()->with(
                'status',
                __('Expense added. It repeats each month on the same calendar day—you can turn that off when you edit it in Records.')
            );
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
                $rule->delete();
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

        $expense->delete();

        return redirect()->back()->with('status', __('Expense deleted successfully.'));
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
