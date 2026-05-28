<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_regular_otp_session_is_redirected_to_admin_login()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_password_set' => true,
        ]);

        // Simulate OTP login — actingAs sets auth but NOT the admin_authenticated flag
        $this->actingAs($admin);

        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_with_admin_session_flag_can_access_dashboard()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_password_set' => true,
        ]);

        // Simulate a proper admin login — auth + session flag
        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_successful_admin_login_sets_admin_session_flag()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_password_set' => true,
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHas('admin_authenticated', true);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_otp_session_admin_sees_login_page_not_infinite_redirect()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_password_set' => true,
        ]);

        // OTP session — authenticated but no admin_authenticated flag
        $response = $this->actingAs($admin)->get(route('admin.login'));

        // Should render the login page, not redirect back to dashboard
        $response->assertStatus(200);
    }

    public function test_non_admin_role_user_cannot_access_admin_panel_even_with_session_flag()
    {
        $user = User::factory()->phoneVerified()->create([
            'role' => 'member',
            'is_admin' => false,
            'is_password_set' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }
}
