<?php

namespace App\Services;

use App\Models\Income;
use App\Models\StudentFundingPlan;
use App\Models\User;

class StudentFundingPlanSync
{
    public function syncLinkedIncome(User $user, StudentFundingPlan $plan): void
    {
        $attributes = [
            'name' => $plan->name,
            'amount' => $plan->amount,
            'date' => $plan->received_on,
        ];

        if ($plan->income_id) {
            $income = Income::query()
                ->where('user_id', $user->id)
                ->whereKey($plan->income_id)
                ->first();

            if ($income) {
                $income->update($attributes);

                return;
            }
        }

        $income = $user->incomes()->create($attributes);
        $plan->update(['income_id' => $income->id]);
    }

    public function deleteLinkedIncome(User $user, StudentFundingPlan $plan): void
    {
        if (! $plan->income_id) {
            return;
        }

        Income::query()
            ->where('user_id', $user->id)
            ->whereKey($plan->income_id)
            ->delete();
    }
}
