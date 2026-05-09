<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Services\RecurringMaterializer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function store(Request $request, RecurringMaterializer $materializer): RedirectResponse
    {
        $validated = $request->validate([
            'income_name' => ['required', 'string', 'max:255'],
            'income_amount' => ['required', 'numeric', 'min:0'],
            'income_date' => ['required', 'date'],
            'income_recurring' => ['sometimes', 'boolean'],
        ], [], [
            'income_name' => __('Income name'),
            'income_amount' => __('Income amount'),
            'income_date' => __('Income date'),
        ]);

        if ($request->boolean('income_recurring')) {
            $date = Carbon::parse($validated['income_date']);
            $request->user()->recurringIncomes()->create([
                'name' => $validated['income_name'],
                'amount' => $validated['income_amount'],
                'day_of_month' => $date->day,
                'starts_on' => $validated['income_date'],
                'ends_on' => null,
            ]);
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());

            return redirect()->back()->with(
                'status',
                __('Income added. It repeats each month on the same calendar day until you remove it under Recurring.')
            );
        }

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
