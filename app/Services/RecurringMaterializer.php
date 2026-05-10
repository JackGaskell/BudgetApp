<?php

namespace App\Services;

use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\User;
use Carbon\Carbon;

class RecurringMaterializer
{
    /**
     * Ensure expense/income rows exist for recurring rules in this calendar month.
     */
    public function materializeMonth(User $user, int $year, int $month): void
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        foreach ($user->recurringExpenses()->cursor() as $rule) {
            if ($rule->isWeekly()) {
                $this->materializeRecurringExpenseWeekly($user, $rule, $monthStart, $monthEnd);
            } else {
                $this->materializeRecurringExpenseMonthly($user, $rule, $monthStart, $monthEnd);
            }
        }

        foreach ($user->recurringIncomes()->cursor() as $rule) {
            if ($rule->isWeekly()) {
                $this->materializeRecurringIncomeWeekly($user, $rule, $monthStart, $monthEnd);
            } else {
                $this->materializeRecurringIncomeMonthly($user, $rule, $monthStart, $monthEnd);
            }
        }
    }

    /**
     * Materialize several months ahead (e.g. scheduled job).
     *
     * @param  int  $monthsAhead  0 = current month only; 2 = current + next two.
     */
    public function materializeUpcomingMonths(User $user, int $monthsAhead = 3): void
    {
        $cursor = now()->copy()->startOfMonth();
        for ($i = 0; $i <= $monthsAhead; $i++) {
            $this->materializeMonth($user, $cursor->year, $cursor->month);
            $cursor->addMonthNoOverflow();
        }
    }

    private function materializeRecurringExpenseMonthly(User $user, RecurringExpense $rule, Carbon $monthStart, Carbon $monthEnd): void
    {
        if ($monthEnd->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $monthStart->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $occurrence = $this->occurrenceDateInMonth((int) $rule->day_of_month, $monthStart->year, $monthStart->month);
        if ($occurrence->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $occurrence->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $this->firstOrCreateExpenseOccurrence($user, $rule, $occurrence);
    }

    private function materializeRecurringExpenseWeekly(User $user, RecurringExpense $rule, Carbon $monthStart, Carbon $monthEnd): void
    {
        if ($rule->day_of_week === null) {
            return;
        }

        if ($monthEnd->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $monthStart->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $startsOn = Carbon::parse($rule->starts_on)->startOfDay();
        $endsOn = $rule->ends_on ? Carbon::parse($rule->ends_on)->endOfDay() : null;
        $targetDow = (int) $rule->day_of_week;

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            if ((int) $cursor->dayOfWeek !== $targetDow) {
                $cursor->addDay();

                continue;
            }

            if ($cursor->lt($startsOn)) {
                $cursor->addDay();

                continue;
            }

            if ($endsOn && $cursor->gt($endsOn)) {
                break;
            }

            $this->firstOrCreateExpenseOccurrence($user, $rule, $cursor->copy());
            $cursor->addDay();
        }
    }

    private function firstOrCreateExpenseOccurrence(User $user, RecurringExpense $rule, Carbon $occurrence): void
    {
        $dateStr = $occurrence->toDateString();

        $user->expenses()->firstOrCreate(
            [
                'recurring_expense_id' => $rule->id,
                'date' => $dateStr,
            ],
            [
                'name' => $rule->name,
                'amount' => $rule->amount,
                'category' => $rule->category,
            ]
        );
    }

    private function materializeRecurringIncomeMonthly(User $user, RecurringIncome $rule, Carbon $monthStart, Carbon $monthEnd): void
    {
        if ($monthEnd->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $monthStart->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $occurrence = $this->occurrenceDateInMonth((int) $rule->day_of_month, $monthStart->year, $monthStart->month);
        if ($occurrence->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $occurrence->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $this->firstOrCreateIncomeOccurrence($user, $rule, $occurrence);
    }

    private function materializeRecurringIncomeWeekly(User $user, RecurringIncome $rule, Carbon $monthStart, Carbon $monthEnd): void
    {
        if ($rule->day_of_week === null) {
            return;
        }

        if ($monthEnd->lt(Carbon::parse($rule->starts_on)->startOfDay())) {
            return;
        }

        if ($rule->ends_on && $monthStart->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return;
        }

        $startsOn = Carbon::parse($rule->starts_on)->startOfDay();
        $endsOn = $rule->ends_on ? Carbon::parse($rule->ends_on)->endOfDay() : null;
        $targetDow = (int) $rule->day_of_week;

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            if ((int) $cursor->dayOfWeek !== $targetDow) {
                $cursor->addDay();

                continue;
            }

            if ($cursor->lt($startsOn)) {
                $cursor->addDay();

                continue;
            }

            if ($endsOn && $cursor->gt($endsOn)) {
                break;
            }

            $this->firstOrCreateIncomeOccurrence($user, $rule, $cursor->copy());
            $cursor->addDay();
        }
    }

    private function firstOrCreateIncomeOccurrence(User $user, RecurringIncome $rule, Carbon $occurrence): void
    {
        $dateStr = $occurrence->toDateString();

        $user->incomes()->firstOrCreate(
            [
                'recurring_income_id' => $rule->id,
                'date' => $dateStr,
            ],
            [
                'name' => $rule->name,
                'amount' => $rule->amount,
            ]
        );
    }

    private function occurrenceDateInMonth(int $dayOfMonth, int $year, int $month): Carbon
    {
        $lastDay = Carbon::create($year, $month, 1)->daysInMonth;
        $day = min(max(1, $dayOfMonth), $lastDay);

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
