<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

            return redirect()->back()->with('status', __('Income added successfully.'));
        }

        $request->user()->incomes()->create([
            'name' => $validated['income_name'],
            'amount' => $validated['income_amount'],
            'date' => $validated['income_date'],
        ]);

        return redirect()->back()->with('status', __('Income added successfully.'));
    }

    public function edit(Request $request, Income $income): View
    {
        $this->authorizeIncome($request, $income);

        return view('income.edit', [
            'income' => $income,
            'return_year' => $request->query('year'),
            'return_month' => $request->query('month'),
        ]);
    }

    public function update(Request $request, Income $income, RecurringMaterializer $materializer, RecurringRuleSync $ruleSync): RedirectResponse
    {
        $this->authorizeIncome($request, $income);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'return_year' => ['nullable', 'integer'],
            'return_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'income_recurring' => ['sometimes', 'boolean'],
        ]);

        $wantsRecurring = $request->boolean('income_recurring');
        $hadRecurring = $income->recurring_income_id !== null;

        if ($hadRecurring && ! $wantsRecurring) {
            $rule = $income->recurringIncome;
            if ($rule && $rule->user_id === $request->user()->id) {
                $rule->delete();
            }
            $income->refresh();
        }

        if ($wantsRecurring && $income->recurring_income_id) {
            $rule = $income->recurringIncome;
            if ($rule !== null) {
                $date = Carbon::parse($validated['date']);
                $rule->update([
                    'name' => $validated['name'],
                    'amount' => $validated['amount'],
                    'day_of_month' => $date->day,
                ]);
                $ruleSync->syncIncomeRuleToTransactions($rule->fresh());
            }
        } elseif ($wantsRecurring && ! $income->recurring_income_id) {
            $income->update([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
            ]);
            $date = Carbon::parse($validated['date']);
            $rule = $request->user()->recurringIncomes()->create([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'day_of_month' => $date->day,
                'starts_on' => $validated['date'],
                'ends_on' => null,
            ]);
            $income->update(['recurring_income_id' => $rule->id]);
            $materializer->materializeMonth($request->user(), $date->year, $date->month);
            $materializer->materializeUpcomingMonths($request->user());
        } else {
            $income->update([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
            ]);
        }

        return $this->redirectToRecordsAfterEdit($request)->with('status', __('Income updated successfully.'));
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        $this->authorizeIncome($request, $income);

        $income->delete();

        return redirect()->back()->with('status', __('Income deleted successfully.'));
    }

    private function authorizeIncome(Request $request, Income $income): void
    {
        if ($income->user_id !== $request->user()->id) {
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
