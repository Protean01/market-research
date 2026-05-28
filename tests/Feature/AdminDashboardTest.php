<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_visit_the_admin_dashboard()
    {
        $admin = User::factory()->phoneVerified()->create(['is_admin' => true, 'role' => 'admin', 'is_password_set' => true]);
        Profile::factory()->create(['user_id' => $admin->id, 'is_complete' => true]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/AdminDashboard'));
    }

    public function test_admin_can_visit_the_user_dashboard()
    {
        $admin = User::factory()->phoneVerified()->create(['is_admin' => true, 'role' => 'admin', 'is_password_set' => true]);
        Profile::factory()->create(['user_id' => $admin->id, 'is_complete' => true]);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_non_admin_is_redirected_to_responder_view()
    {
        $user = User::factory()->phoneVerified()->create(['is_admin' => false, 'is_password_set' => true]);
        Profile::factory()->create(['user_id' => $user->id, 'is_complete' => true]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }
}
