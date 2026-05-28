<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('onboarding'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->phoneVerified()->create();
        Profile::factory()->create(['user_id' => $user->id, 'is_complete' => true]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
