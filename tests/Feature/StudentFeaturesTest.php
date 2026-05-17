<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_show_student_prompt_banner(): void
    {
        $user = User::factory()->create(['is_student' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSeeText('Budgeting around student loans?');
        $response->assertDontSeeText('Yes, I’m a student');
    }

    public function test_student_sees_loan_planning_card_on_dashboard(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Student loan planning');
    }

    public function test_non_student_does_not_see_loan_planning_card(): void
    {
        $user = User::factory()->create(['is_student' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSeeText('Student loan planning');
    }

    public function test_profile_can_toggle_student_flag(): void
    {
        $user = User::factory()->create(['is_student' => false]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'is_student' => '1',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue($user->fresh()->is_student);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
        ])->assertRedirect(route('profile.edit'));

        $this->assertFalse($user->fresh()->is_student);
    }
}
