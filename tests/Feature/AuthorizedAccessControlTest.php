<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_authorized_user_with_position_and_selected_features(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.authorized.store'), [
                'username' => 'Maria Santos',
                'email' => 'maria.authorized@example.com',
                'position' => 'SSC President',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'status' => '1',
                'authorized_permissions' => ['campus_events', 'hazard_points'],
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $authorizedUser = User::where('email', 'maria.authorized@example.com')->firstOrFail();

        $this->assertSame('authorized_user', $authorizedUser->role);
        $this->assertSame('SSC President', $authorizedUser->position);
        $this->assertSame(['campus_events', 'hazard_points'], $authorizedUser->authorized_permissions);
        $this->assertTrue(Hash::check('SecurePass123!', $authorizedUser->password));
    }

    public function test_admin_can_change_authorized_user_position_status_and_feature_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'position' => 'SSC Officer',
            'authorized_permissions' => ['campus_events'],
            'status' => '1',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.authorized.update', $authorizedUser), [
                'username' => $authorizedUser->username,
                'email' => $authorizedUser->email,
                'position' => 'SSC President',
                'password' => '',
                'password_confirmation' => '',
                'status' => '0',
                'authorized_permissions' => ['hazard_points', 'buildings'],
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $authorizedUser->refresh();

        $this->assertSame('SSC President', $authorizedUser->position);
        $this->assertSame('0', (string) $authorizedUser->status);
        $this->assertSame(['hazard_points', 'buildings'], $authorizedUser->authorized_permissions);
    }

    public function test_authorized_user_can_access_only_features_assigned_by_admin(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
            'position' => 'SSC President',
            'authorized_permissions' => ['campus_events'],
        ]);

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.campus-event'))
            ->assertOk();

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.hazard-point'))
            ->assertForbidden();

        $this
            ->actingAs($authorizedUser)
            ->post(route('admin.hazard-point.store'))
            ->assertForbidden();
    }

    public function test_authorized_dashboard_lists_assigned_features_and_hides_unassigned_features(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
            'position' => 'CAMPUS INFORMATION OFFICER',
            'authorized_permissions' => ['campus_events', 'hazard_points'],
        ]);

        $this
            ->actingAs($authorizedUser)
            ->get(route('authorized.dashboard'))
            ->assertOk()
            ->assertSee('<base href="'.url('/admin').'/"', false)
            ->assertSee('Campus Information Officer')
            ->assertDontSee('CAMPUS INFORMATION OFFICER')
            ->assertSee('Dashboard.')
            ->assertSee('Signed in as '.$authorizedUser->username)
            ->assertSee('Campus Operations Center')
            ->assertSee('Campus Events')
            ->assertSee('Hazard Points')
            ->assertDontSee('Indoor Maps');
    }

    public function test_authorized_user_is_redirected_to_the_authorized_dashboard_after_login(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
            'password' => 'password',
        ]);

        $this
            ->post('/login', [
                'email' => $authorizedUser->email,
                'password' => 'password',
            ])
            ->assertRedirect('authorized/dashboard');
    }

    public function test_authorized_user_cannot_manage_authorized_accounts(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
            'position' => 'SSC President',
            'authorized_permissions' => ['campus_events'],
        ]);

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.authorized.index'))
            ->assertForbidden();
    }

    public function test_admin_retains_access_to_all_feature_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.hazard-point'))
            ->assertOk();
    }

    public function test_admin_interface_uses_authorized_access_label(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.authorized.index'))
            ->assertOk()
            ->assertSeeText('Authorized access management')
            ->assertSeeText('Register authorized account')
            ->assertSeeText('Authorized accounts');
    }

    public function test_user_seeder_creates_an_authorized_account(): void
    {
        $this->seed(UserTableSeeder::class);

        $authorizedUser = User::where('email', 'authorized@gmail.com')->firstOrFail();

        $this->assertSame('authorized_user', $authorizedUser->role);
        $this->assertSame('Supreme Student Council', $authorizedUser->position);
        $this->assertSame(
            ['campus_events', 'hazard_points'],
            $authorizedUser->authorized_permissions,
        );
    }
}
