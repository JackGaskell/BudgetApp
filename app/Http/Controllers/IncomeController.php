<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\RecurringIncome;
use App\Services\RecurringMaterializer;
use App\Services\RecurringRuleSync;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                DB::transaction(function () use ($rule, $income): void {
                    $rule->incomes()->whereKeyNot($income->id)->delete();
                    $rule->delete();
                });
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

        DB::transaction(function () use ($request, $income): void {
            $userId = $request->user()->id;
            $rule = $income->recurringIncome;
            if ($rule !== null && $rule->user_id === $userId) {
                $canonicalName = trim($rule->name);
                $billingDay = (int) $rule->day_of_month;
            } else {
                $canonicalName = trim($income->name);
                $billingDay = (int) Carbon::parse($income->date)->day;
            }

            $this->purgeRecurringIncomeRulesForUserByNameAndDay($userId, $canonicalName, $billingDay);

            Income::query()->whereKey($income->id)->where('user_id', $userId)->delete();

            $this->deleteOrphanIncomesByNameAndDay($userId, $canonicalName, $billingDay);
        });

        return redirect()->back()->with('status', __('Income deleted successfully.'));
    }

    private function purgeRecurringIncomeRulesForUserByNameAndDay(int $userId, string $canonicalName, int $billingDay): void
    {
        $rules = RecurringIncome::query()
            ->where('user_id', $userId)
            ->where('day_of_month', $billingDay)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->get();

        foreach ($rules as $rule) {
            $rule->incomes()->delete();
            $rule->delete();
        }
    }

    private function deleteOrphanIncomesByNameAndDay(int $userId, string $canonicalName, int $billingDay): void
    {
        Income::query()
            ->where('user_id', $userId)
            ->whereNull('recurring_income_id')
            ->whereDay('date', $billingDay)
            ->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$canonicalName])
            ->delete();
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
