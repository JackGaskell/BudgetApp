<?php

namespace App\Services;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Carbon\Carbon;

class RecurringRuleSync
{
    /**
     * Push rule fields (and per-month dates from day_of_month) to every linked transaction.
     */
    public function syncExpenseRuleToTransactions(RecurringExpense $rule): void
    {
        foreach ($rule->expenses()->cursor() as $expense) {
            $y = (int) Carbon::parse($expense->date)->year;
            $m = (int) Carbon::parse($expense->date)->month;
            $lastDay = Carbon::create($y, $m, 1)->daysInMonth;
            $day = min(max(1, $rule->day_of_month), $lastDay);
            $dateStr = Carbon::create($y, $m, $day)->toDateString();
            $expense->update([
                'name' => $rule->name,
                'amount' => $rule->amount,
                'category' => $rule->category,
                'date' => $dateStr,
            ]);
        }
    }

    public function syncIncomeRuleToTransactions(RecurringIncome $rule): void
    {
        foreach ($rule->incomes()->cursor() as $income) {
            $y = (int) Carbon::parse($income->date)->year;
            $m = (int) Carbon::parse($income->date)->month;
            $lastDay = Carbon::create($y, $m, 1)->daysInMonth;
            $day = min(max(1, $rule->day_of_month), $lastDay);
            $dateStr = Carbon::create($y, $m, $day)->toDateString();
            $income->update([
                'name' => $rule->name,
                'amount' => $rule->amount,
                'date' => $dateStr,
            ]);
        }
    }
}
