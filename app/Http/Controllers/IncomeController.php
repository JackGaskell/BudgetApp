<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'income_name' => ['required', 'string', 'max:255'],
            'income_amount' => ['required', 'numeric', 'min:0'],
            'income_date' => ['required', 'date'],
        ], [], [
            'income_name' => __('Income name'),
            'income_amount' => __('Income amount'),
            'income_date' => __('Income date'),
        ]);

        $request->user()->incomes()->create([
            'name' => $validated['income_name'],
            'amount' => $validated['income_amount'],
            'date' => $validated['income_date'],
        ]);

        return redirect()->back()->with('status', __('Income added successfully.'));
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $income->delete();

        return redirect()->back()->with('status', __('Income deleted successfully.'));
    }
}
