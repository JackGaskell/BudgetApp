<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\RecurringMaterializer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                __('Expense added. It repeats each month on the same calendar day until you remove it under Recurring.')
            );
        }

        $request->user()->expenses()->create($validated);

        return redirect()->back()->with('status', __('Expense added successfully.'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);

        $validated = $request->validate($this->expenseValidationRules());

        $expense->update($validated);

        return redirect()->route('records.index')->with('status', __('Expense updated successfully.'));
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
}
