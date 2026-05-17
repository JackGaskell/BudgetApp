<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentFundingPlanRequest;
use App\Services\StudentFundingPlanSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class StudentFundingPlanController extends Controller
{
    public function store(StoreStudentFundingPlanRequest $request, StudentFundingPlanSync $sync): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $existing = $user->studentFundingPlan()->first();

        if ($existing) {
            $existing->update($validated);
            $sync->syncLinkedIncome($user, $existing->fresh());

            return Redirect::route('dashboard')->with('status', __('Loan plan updated.'));
        }

        $plan = $user->studentFundingPlan()->create($validated);
        $sync->syncLinkedIncome($user, $plan);

        return Redirect::route('dashboard')->with('status', __('Loan plan saved.'));
    }

    public function destroy(StudentFundingPlanSync $sync): RedirectResponse
    {
        $user = request()->user();

        if (! $user->hasStudentFeatures()) {
            abort(403);
        }

        $plan = $user->studentFundingPlan()->first();

        if ($plan) {
            $sync->deleteLinkedIncome($user, $plan);
            $plan->delete();
        }

        return Redirect::route('dashboard')->with('status', __('Loan plan removed.'));
    }
}
