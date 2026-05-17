<?php

namespace Tests\Feature;

use App\Models\Income;
use App\Models\StudentFundingPlan;
use App\Models\User;
use App\Services\StudentFundingPlanCalculator;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFundingPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function validPlanPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maintenance loan',
            'amount' => '3000',
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
            'spread_frequency' => StudentFundingPlan::FREQUENCY_MONTHLY,
        ], $overrides);
    }

    public function test_non_student_cannot_store_funding_plan(): void
    {
        $user = User::factory()->create(['is_student' => false]);

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload())
            ->assertForbidden();
    }

    public function test_student_can_store_funding_plan_and_linked_income(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload())
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $plan = $user->fresh()->studentFundingPlan;
        $this->assertNotNull($plan);
        $this->assertSame('Maintenance loan', $plan->name);
        $this->assertNotNull($plan->income_id);

        $income = Income::query()->find($plan->income_id);
        $this->assertNotNull($income);
        $this->assertSame('3000.00', (string) $income->amount);
        $this->assertStringStartsWith('2026-01-01', (string) $income->date);
    }

    public function test_student_can_update_existing_plan(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload());

        $plan = $user->fresh()->studentFundingPlan;
        $incomeId = $plan->income_id;

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'amount' => '3500',
                'name' => 'Updated loan',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $plan->refresh();
        $this->assertSame($incomeId, $plan->income_id);
        $this->assertSame('Updated loan', $plan->name);
        $this->assertSame('3500.00', (string) Income::find($incomeId)->amount);
    }

    public function test_student_can_destroy_plan_and_linked_income(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload());
        $incomeId = $user->fresh()->studentFundingPlan->income_id;

        $this->actingAs($user)
            ->delete(route('student.funding-plan.destroy'))
            ->assertRedirect(route('dashboard'));

        $this->assertNull($user->fresh()->studentFundingPlan);
        $this->assertNull(Income::find($incomeId));
    }

    public function test_calculator_includes_other_income_in_period(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->student()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
            'spread_frequency' => StudentFundingPlan::FREQUENCY_MONTHLY,
        ]);

        $user->incomes()->create([
            'name' => 'Student loan',
            'amount' => 3000,
            'date' => '2026-01-01',
        ]);

        $user->incomes()->create([
            'name' => 'Part-time job',
            'amount' => 500,
            'date' => '2026-02-01',
        ]);

        $user->expenses()->create([
            'name' => 'Rent',
            'amount' => 400,
            'date' => '2026-02-01',
            'category' => 'Housing & Utilities',
        ]);

        $snapshot = app(StudentFundingPlanCalculator::class)->snapshot($user, $plan);

        $this->assertSame(3500.0, $snapshot['income_so_far']);
        $this->assertSame(400.0, $snapshot['expenses_so_far']);
        $this->assertSame(3100.0, $snapshot['net_so_far']);
        $this->assertSame(600.0, $snapshot['spread_amount']);

        Carbon::setTestNow();
    }

    public function test_dashboard_shows_snapshot_when_plan_exists(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->student()->create();
        StudentFundingPlan::factory()->for($user)->create([
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Spread from this loan');
        $response->assertSeeText('Income this period');

        Carbon::setTestNow();
    }

    public function test_guest_cannot_store_or_destroy_funding_plan(): void
    {
        $this->post(route('student.funding-plan.store'), $this->validPlanPayload())
            ->assertRedirect(route('login'));

        $this->delete(route('student.funding-plan.destroy'))
            ->assertRedirect(route('login'));
    }

    public function test_non_student_cannot_destroy_funding_plan(): void
    {
        $user = User::factory()->create(['is_student' => false]);

        $this->actingAs($user)
            ->delete(route('student.funding-plan.destroy'))
            ->assertForbidden();
    }

    public function test_destroy_without_plan_still_redirects(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->delete(route('student.funding-plan.destroy'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');
    }

    public function test_store_rejects_next_payment_on_before_received_on(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'received_on' => '2026-05-01',
                'next_payment_on' => '2026-01-01',
            ]))
            ->assertSessionHasErrors('next_payment_on');

        $this->assertNull($user->fresh()->studentFundingPlan);
    }

    public function test_store_rejects_invalid_spread_frequency(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'spread_frequency' => 'daily',
            ]))
            ->assertSessionHasErrors('spread_frequency');
    }

    public function test_store_rejects_negative_amount(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'amount' => '-10',
            ]))
            ->assertSessionHasErrors('amount');
    }

    public function test_store_rejects_amount_above_max(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'amount' => (string) ((float) Money::MAX_AMOUNT + 1),
            ]))
            ->assertSessionHasErrors('amount');
    }

    public function test_store_rejects_missing_name(): void
    {
        $user = User::factory()->student()->create();

        $payload = $this->validPlanPayload();
        unset($payload['name']);

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $payload)
            ->assertSessionHasErrors('name');
    }

    public function test_updating_received_on_updates_linked_income_date(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload());

        $incomeId = $user->fresh()->studentFundingPlan->income_id;

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload([
            'received_on' => '2026-02-01',
        ]));

        $income = Income::find($incomeId);
        $this->assertStringStartsWith('2026-02-01', (string) $income->date);
    }

    public function test_weekly_plan_is_persisted_and_shown_on_dashboard(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload([
            'spread_frequency' => StudentFundingPlan::FREQUENCY_WEEKLY,
        ]));

        $this->assertSame(
            StudentFundingPlan::FREQUENCY_WEEKLY,
            $user->fresh()->studentFundingPlan->spread_frequency
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('/ week');
    }

    public function test_student_without_plan_sees_setup_form_not_snapshot(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Save loan plan');
        $response->assertDontSeeText('Spread from this loan');
        $response->assertDontSeeText('Income this period');
    }

    public function test_loan_income_counts_toward_dashboard_balance_in_arrival_month(): void
    {
        Carbon::setTestNow('2026-03-15');

        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload([
            'amount' => '2500',
            'received_on' => '2026-03-01',
            'next_payment_on' => '2026-07-01',
        ]));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('£2,500.00');
    }

    public function test_removing_plan_removes_loan_from_dashboard_balance(): void
    {
        Carbon::setTestNow('2026-03-15');

        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload([
            'amount' => '2500',
            'received_on' => '2026-03-01',
            'next_payment_on' => '2026-07-01',
        ]));

        $this->actingAs($user)->get(route('dashboard'))->assertSee('£2,500.00');

        $this->actingAs($user)->delete(route('student.funding-plan.destroy'));

        $this->assertNull(Income::query()->where('user_id', $user->id)->first());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Current balance')
            ->assertSee('£0.00');
    }

    public function test_second_store_updates_instead_of_creating_duplicate_plan(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('student.funding-plan.store'), $this->validPlanPayload());
        $firstId = $user->fresh()->studentFundingPlan->id;

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload(['name' => 'Second save']))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, StudentFundingPlan::query()->where('user_id', $user->id)->count());
        $this->assertSame($firstId, $user->fresh()->studentFundingPlan->id);
        $this->assertSame('Second save', $user->fresh()->studentFundingPlan->name);
    }

    public function test_validation_errors_are_shown_on_dashboard_form(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->post(route('student.funding-plan.store'), $this->validPlanPayload([
                'amount' => '',
            ]))
            ->assertSessionHasErrors('amount');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Student loan planning');
    }

    public function test_dashboard_shows_loan_pace_warning_when_over_pace(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->student()->create();
        $plan = StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->incomes()->create(['name' => 'Loan', 'amount' => 3000, 'date' => '2026-01-01']);
        $user->expenses()->create([
            'name' => 'Heavy spend',
            'amount' => 2500,
            'date' => '2026-02-01',
            'category' => 'Food',
        ]);

        app(StudentFundingPlanCalculator::class)->snapshot($user, $plan);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('above the even loan pace');
    }

    public function test_dashboard_shows_on_track_message_when_under_loan_pace(): void
    {
        Carbon::setTestNow('2026-02-15');

        $user = User::factory()->student()->create();
        StudentFundingPlan::factory()->for($user)->create([
            'amount' => 3000,
            'received_on' => '2026-01-01',
            'next_payment_on' => '2026-05-01',
        ]);

        $user->incomes()->create(['name' => 'Loan', 'amount' => 3000, 'date' => '2026-01-01']);
        $user->expenses()->create([
            'name' => 'Light spend',
            'amount' => 100,
            'date' => '2026-02-01',
            'category' => 'Food',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('on track with your loan pace');
    }
}
