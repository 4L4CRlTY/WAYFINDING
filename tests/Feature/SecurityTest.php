<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_generic_dashboard_and_profile_routes_are_removed(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => '1',
        ]);

        $this->assertFalse(Route::has('dashboard'));
        $this->assertFalse(Route::has('profile.edit'));
        $this->assertFalse(Route::has('profile.update'));
        $this->assertFalse(Route::has('profile.destroy'));

        $this->actingAs($user)->get('/dashboard')->assertNotFound();
        $this->actingAs($user)->get('/profile')->assertNotFound();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'status' => '0',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect('/');
    }

    public function test_existing_session_is_terminated_when_account_becomes_inactive(): void
    {
        $user = User::factory()->create([
            'status' => '0',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard'));

        $this->assertGuest();
        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect(route('login'));
    }

    public function test_user_role_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => '1',
        ]);

        $this
            ->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_logout_only_accepts_post_and_terminates_the_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/logout')
            ->assertMethodNotAllowed();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.logout'));

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
