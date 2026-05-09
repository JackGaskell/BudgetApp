<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Services\RecurringRuleSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecurringController extends Controller
{
    public function index(Request $request): View
    {
        return view('recurring.index', [
            'recurring_expenses' => $request->user()->recurringExpenses()->orderBy('name')->get(),
            'recurring_incomes' => $request->user()->recurringIncomes()->orderBy('name')->get(),
        ]);
    }

    public function destroyExpense(Request $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorizeRecurringExpense($request, $recurringExpense);

        $recurringExpense->delete();

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Monthly expense removed. Existing transactions stay in your records unless you delete them.'));
    }

    public function editExpense(Request $request, RecurringExpense $recurringExpense): View
    {
        $this->authorizeRecurringExpense($request, $recurringExpense);

        return view('recurring.edit-expense', [
            'recurringExpense' => $recurringExpense,
            'expense_categories' => Expense::CATEGORIES,
        ]);
    }

    public function updateExpense(Request $request, RecurringExpense $recurringExpense, RecurringRuleSync $ruleSync): RedirectResponse
    {
        $this->authorizeRecurringExpense($request, $recurringExpense);

        $allowedCategories = implode(',', Expense::CATEGORIES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'in:'.$allowedCategories],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ], [], [
            'day_of_month' => __('Day of month'),
        ]);

        $recurringExpense->update([
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'category' => $validated['category'],
            'day_of_month' => $validated['day_of_month'],
        ]);

        $ruleSync->syncExpenseRuleToTransactions($recurringExpense->fresh());

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Repeating expense updated. All months linked to this repeat were updated.'));
    }

    public function editIncome(Request $request, RecurringIncome $recurringIncome): View
    {
        $this->authorizeRecurringIncome($request, $recurringIncome);

        return view('recurring.edit-income', [
            'recurringIncome' => $recurringIncome,
        ]);
    }

    public function updateIncome(Request $request, RecurringIncome $recurringIncome, RecurringRuleSync $ruleSync): RedirectResponse
    {
        $this->authorizeRecurringIncome($request, $recurringIncome);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ], [], [
            'day_of_month' => __('Day of month'),
        ]);

        $recurringIncome->update($validated);

        $ruleSync->syncIncomeRuleToTransactions($recurringIncome->fresh());

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Repeating income updated. All months linked to this repeat were updated.'));
    }

    public function destroyIncome(Request $request, RecurringIncome $recurringIncome): RedirectResponse
    {
        $this->authorizeRecurringIncome($request, $recurringIncome);

        $recurringIncome->delete();

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Monthly income removed. Existing transactions stay in your records unless you delete them.'));
    }

    private function authorizeRecurringExpense(Request $request, RecurringExpense $recurringExpense): void
    {
        if ($recurringExpense->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function authorizeRecurringIncome(Request $request, RecurringIncome $recurringIncome): void
    {
        if ($recurringIncome->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
