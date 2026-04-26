<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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

    public function store(Request $request): RedirectResponse
    {
        $allowedCategories = implode(',', Expense::CATEGORIES);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'category' => ['required', 'string', 'in:'.$allowedCategories],
            'date' => ['required', 'date'],
        ]);

        $request->user()->expenses()->create($validated);

        return redirect()->back()->with('status', 'Expense added successfully.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->back()->with('status', 'Expense deleted successfully.');
    }
}
