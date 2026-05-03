<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->expenseValidationRules());

        $request->user()->expenses()->create($validated);

        return redirect()->back()->with('status', 'Expense added successfully.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);

        $validated = $request->validate($this->expenseValidationRules());

        $expense->update($validated);

        return redirect()->route('records.index')->with('status', 'Expense updated successfully.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);

        $expense->delete();

        return redirect()->back()->with('status', 'Expense deleted successfully.');
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
}
