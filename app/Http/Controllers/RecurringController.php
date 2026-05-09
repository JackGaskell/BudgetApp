<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Services\RecurringMaterializer;
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
            'expense_categories' => Expense::CATEGORIES,
        ]);
    }

    public function storeExpense(Request $request, RecurringMaterializer $materializer): RedirectResponse
    {
        $allowedCategories = implode(',', Expense::CATEGORIES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'in:'.$allowedCategories],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ], [], [
            'day_of_month' => __('Day of month'),
            'starts_on' => __('Starts on'),
            'ends_on' => __('Ends on'),
        ]);

        $request->user()->recurringExpenses()->create($validated);

        $materializer->materializeUpcomingMonths($request->user());

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Monthly expense saved. Matching entries are added to each month automatically.'));
    }

    public function storeIncome(Request $request, RecurringMaterializer $materializer): RedirectResponse
    {
        $validated = $request->validate([
            'income_name' => ['required', 'string', 'max:255'],
            'income_amount' => ['required', 'numeric', 'min:0'],
            'income_day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'income_starts_on' => ['required', 'date'],
            'income_ends_on' => ['nullable', 'date', 'after_or_equal:income_starts_on'],
        ], [], [
            'income_name' => __('Name'),
            'income_amount' => __('Amount'),
            'income_day_of_month' => __('Day of month'),
            'income_starts_on' => __('Starts on'),
            'income_ends_on' => __('Ends on'),
        ]);

        $request->user()->recurringIncomes()->create([
            'name' => $validated['income_name'],
            'amount' => $validated['income_amount'],
            'day_of_month' => $validated['income_day_of_month'],
            'starts_on' => $validated['income_starts_on'],
            'ends_on' => $validated['income_ends_on'] ?? null,
        ]);

        $materializer->materializeUpcomingMonths($request->user());

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Monthly income saved. Matching entries are added to each month automatically.'));
    }

    public function destroyExpense(Request $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorizeRecurringExpense($request, $recurringExpense);

        $recurringExpense->delete();

        return redirect()
            ->route('recurring.index')
            ->with('status', __('Monthly expense removed. Existing transactions stay in your records unless you delete them.'));
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
