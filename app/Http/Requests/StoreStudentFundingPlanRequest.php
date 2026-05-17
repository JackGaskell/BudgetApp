<?php

namespace App\Http\Requests;

use App\Models\StudentFundingPlan;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentFundingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasStudentFeatures() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:'.Money::MAX_AMOUNT],
            'received_on' => ['required', 'date'],
            'next_payment_on' => ['required', 'date', 'after:received_on'],
            'spread_frequency' => [
                'required',
                Rule::in([StudentFundingPlan::FREQUENCY_WEEKLY, StudentFundingPlan::FREQUENCY_MONTHLY]),
            ],
        ];
    }
}
