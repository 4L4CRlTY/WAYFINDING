<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_access_searches_account_fields_without_leaking_other_roles(): void
    {
        $actingAdmin = User::factory()->create([
            'username' => 'Search Test Administrator',
            'role' => 'admin',
            'status' => '1',
        ]);

        $usernameMatch = $this->createAuthorizedUser([
            'username' => 'Username Atlas Match',
            'email' => 'atlas-authorized@example.test',
            'position' => 'Records Officer',
        ]);

        $emailMatch = $this->createAuthorizedUser([
            'username' => 'Beacon Email Match',
            'email' => 'beacon-authorized@example.test',
            'position' => 'Navigation Officer',
        ]);

        $positionMatch = $this->createAuthorizedUser([
            'username' => 'Position Signal Match',
            'email' => 'signal-authorized@example.test',
            'position' => 'Signal Command',
        ]);

        $unrelatedAuthorizedUser = $this->createAuthorizedUser([
            'username' => 'Unrelated Authorized Account',
            'email' => 'unrelated-authorized@example.test',
            'position' => 'General Office',
        ]);

        $roleLeakAdmin = User::factory()->create([
            'username' => 'Username Atlas Admin Leak',
            'email' => 'beacon-admin@example.test',
            'position' => 'Signal Command',
            'role' => 'admin',
            'status' => '1',
        ]);

        $roleLeakUser = User::factory()->create([
            'username' => 'Username Atlas User Leak',
            'email' => 'beacon-user@example.test',
            'position' => 'Signal Command',
            'role' => 'user',
            'status' => '1',
        ]);

        $cases = [
            ['Username Atlas', $usernameMatch],
            ['beacon-authorized', $emailMatch],
            ['Signal Command', $positionMatch],
        ];

        foreach ($cases as [$search, $expectedAccount]) {
            $response = $this
                ->actingAs($actingAdmin)
                ->get(route('admin.authorized.index', ['search' => $search]));

            $response
                ->assertOk()
                ->assertSeeText($expectedAccount->username)
                ->assertDontSeeText($unrelatedAuthorizedUser->username)
                ->assertDontSeeText($roleLeakAdmin->username)
                ->assertDontSeeText($roleLeakUser->username);

            foreach ([$usernameMatch, $emailMatch, $positionMatch] as $authorizedAccount) {
                if ($authorizedAccount->isNot($expectedAccount)) {
                    $response->assertDontSeeText($authorizedAccount->username);
                }
            }
        }
    }

    public function test_campus_event_search_matches_related_building_and_room_names(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $eventBuilding = $this->createBuilding('Aurora Research Hall');
        $roomBuilding = $this->createBuilding('Borealis Academic Wing');
        $unrelatedBuilding = $this->createBuilding('Cedar Administration Hall');

        $indoorMap = IndoorMap::create([
            'building_id' => $roomBuilding->id,
            'name' => 'Borealis First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'is_active' => true,
        ]);

        $room = IndoorRoom::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Quantum Collaboration Suite',
            'room_code' => 'QCS-101',
            'type' => 'classroom',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        $buildingEvent = CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $eventBuilding->id,
            'created_by' => $admin->id,
            'title' => 'Architecture Research Symposium',
            'starts_at' => now()->addDay(),
            'is_active' => true,
            'priority' => 3,
        ]);

        $roomEvent = CampusEvent::create([
            'event_target_type' => 'room',
            'indoor_room_id' => $room->id,
            'created_by' => $admin->id,
            'title' => 'Collaborative Systems Workshop',
            'starts_at' => now()->addDays(2),
            'is_active' => true,
            'priority' => 2,
        ]);

        $unrelatedEvent = CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $unrelatedBuilding->id,
            'created_by' => $admin->id,
            'title' => 'Unrelated Governance Meeting',
            'starts_at' => now()->addDays(3),
            'is_active' => true,
            'priority' => 1,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.campus-event', ['search' => 'Aurora Research Hall']))
            ->assertOk()
            ->assertSeeText($buildingEvent->title)
            ->assertDontSeeText($roomEvent->title)
            ->assertDontSeeText($unrelatedEvent->title);

        $this
            ->actingAs($admin)
            ->get(route('admin.campus-event', ['search' => 'Quantum Collaboration Suite']))
            ->assertOk()
            ->assertSeeText($roomEvent->title)
            ->assertDontSeeText($buildingEvent->title)
            ->assertDontSeeText($unrelatedEvent->title);
    }

    public function test_admin_layout_loads_table_tools_and_light_palette_without_touching_user_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-bs-theme="light"', false)
            ->assertSee('data-topbar-color="light"', false)
            ->assertSee(asset('admin/assets/css/futuristic-admin.css'), false)
            ->assertSee(asset('admin/assets/js/admin-table-tools.js'), false);

        $palette = file_get_contents(public_path('admin/assets/css/futuristic-admin.css'));

        $this->assertStringContainsString('--admin-primary: #18375d;', $palette);
        $this->assertStringContainsString('--admin-primary-bright: #68a7ee;', $palette);
        $this->assertStringContainsString('--ct-body-bg: #ffffff;', $palette);

        $regularUser = User::factory()->create([
            'role' => 'user',
            'status' => '1',
        ]);

        $this
            ->actingAs($regularUser)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertDontSee(asset('admin/assets/js/admin-table-tools.js'), false)
            ->assertDontSee(asset('admin/assets/css/futuristic-admin.css'), false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAuthorizedUser(array $attributes): User
    {
        return User::factory()->create(array_merge([
            'role' => 'authorized_user',
            'authorized_permissions' => ['campus_events'],
            'status' => '1',
        ], $attributes));
    }

    private function createBuilding(string $name): Building
    {
        return Building::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
            'color' => '#18375d',
        ]);
    }
}
