<?php

namespace App\Http\Controllers;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
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
