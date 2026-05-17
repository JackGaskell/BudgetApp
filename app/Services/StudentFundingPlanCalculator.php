<?php

namespace App\Services;

use App\Models\StudentFundingPlan;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class StudentFundingPlanCalculator
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user, StudentFundingPlan $plan, ?CarbonInterface $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $start = Carbon::parse($plan->received_on)->startOfDay();
        $end = Carbon::parse($plan->next_payment_on)->startOfDay();

        $loanAmount = (float) $plan->amount;
        $totalDays = max(1, $start->diffInDays($end));
        $daysElapsed = min($totalDays, max(0, $start->diffInDays($asOf)));
        $daysRemaining = max(0, $asOf->diffInDays($end, false));

        $periodEndSoFar = $asOf->lt($end) ? $asOf : $end;

        $incomeSoFar = (float) $user->incomes()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $periodEndSoFar)
            ->sum('amount');

        $expensesSoFar = (float) $user->expenses()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $periodEndSoFar)
            ->sum('amount');

        $incomeFullPeriod = (float) $user->incomes()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        $expensesFullPeriod = (float) $user->expenses()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('amount');

        $spread = $this->spreadAllowance($plan, $start, $end);

        $expectedSpendByNow = $loanAmount * ($daysElapsed / $totalDays);
        $overLoanPaceBy = max(0, $expensesSoFar - $expectedSpendByNow);

        return [
            'plan' => $plan,
            'start' => $start,
            'end' => $end,
            'as_of' => $asOf,
            'loan_amount' => $loanAmount,
            'total_days' => $totalDays,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'spread_amount' => $spread['amount'],
            'spread_frequency' => $spread['frequency'],
            'spread_periods' => $spread['periods'],
            'income_so_far' => $incomeSoFar,
            'expenses_so_far' => $expensesSoFar,
            'net_so_far' => $incomeSoFar - $expensesSoFar,
            'income_full_period' => $incomeFullPeriod,
            'expenses_full_period' => $expensesFullPeriod,
            'net_full_period' => $incomeFullPeriod - $expensesFullPeriod,
            'expected_spend_by_now' => $expectedSpendByNow,
            'over_loan_pace_by' => $overLoanPaceBy,
            'on_loan_pace' => $overLoanPaceBy <= 0.01,
            'loan_remaining_simple' => max(0, $loanAmount - $expensesSoFar),
        ];
    }

    /**
     * @return array{amount: float, frequency: string, periods: int}
     */
    private function spreadAllowance(StudentFundingPlan $plan, Carbon $start, Carbon $end): array
    {
        $loanAmount = (float) $plan->amount;

        if ($plan->spread_frequency === StudentFundingPlan::FREQUENCY_WEEKLY) {
            $weeks = max(1, (int) ceil($start->diffInDays($end) / 7));

            return [
                'amount' => $loanAmount / $weeks,
                'frequency' => StudentFundingPlan::FREQUENCY_WEEKLY,
                'periods' => $weeks,
            ];
        }

        $months = 0;
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months++;
            $cursor->addMonth();
        }

        $months = max(1, $months);

        return [
            'amount' => $loanAmount / $months,
            'frequency' => StudentFundingPlan::FREQUENCY_MONTHLY,
            'periods' => $months,
        ];
    }
}
