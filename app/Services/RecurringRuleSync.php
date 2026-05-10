<?php

namespace App\Services;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use Carbon\Carbon;

class RecurringRuleSync
{
    /**
     * Push rule fields to every linked transaction (and dates for monthly rules).
     */
    public function syncExpenseRuleToTransactions(RecurringExpense $rule): void
    {
        if ($rule->isWeekly()) {
            foreach ($rule->expenses()->cursor() as $expense) {
                $expense->update([
                    'name' => $rule->name,
                    'amount' => $rule->amount,
                    'category' => $rule->category,
                ]);
            }

            return;
        }

        foreach ($rule->expenses()->cursor() as $expense) {
            $y = (int) Carbon::parse($expense->date)->year;
            $m = (int) Carbon::parse($expense->date)->month;
            $lastDay = Carbon::create($y, $m, 1)->daysInMonth;
            $day = min(max(1, (int) $rule->day_of_month), $lastDay);
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
        if ($rule->isWeekly()) {
            foreach ($rule->incomes()->cursor() as $income) {
                $income->update([
                    'name' => $rule->name,
                    'amount' => $rule->amount,
                ]);
            }

            return;
        }

        foreach ($rule->incomes()->cursor() as $income) {
            $y = (int) Carbon::parse($income->date)->year;
            $m = (int) Carbon::parse($income->date)->month;
            $lastDay = Carbon::create($y, $m, 1)->daysInMonth;
            $day = min(max(1, (int) $rule->day_of_month), $lastDay);
            $dateStr = Carbon::create($y, $m, $day)->toDateString();
            $income->update([
                'name' => $rule->name,
                'amount' => $rule->amount,
                'date' => $dateStr,
            ]);
        }
    }
}
