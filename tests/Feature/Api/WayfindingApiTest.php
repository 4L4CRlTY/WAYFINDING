<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\DestinationKeyword;
use App\Models\IndoorMap;
use App\Models\IndoorPath;
use App\Models\IndoorRoom;
use App\Models\Path;
use App\Models\User;
use App\Support\WayfindingCache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WayfindingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_public_wayfinding_endpoints_are_available(): void
    {
        $endpoints = [
            '/api/buildings',
            '/api/paths',
            '/api/entry-points',
            '/api/building-entrances',
            '/api/hazard-points',
            '/api/landuses',
            '/api/indoor-maps',
            '/api/indoor-rooms',
            '/api/indoor-paths',
            '/api/indoor-entrances',
            '/api/building-entrance-links',
            '/api/indoor-stairs-links',
            '/api/campus-events',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)->assertOk();
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_buildings_endpoint_returns_ordered_decoded_geometry(): void
    {
        $second = Building::create([
            'name' => 'Zulu Hall',
            'geometry' => $this->buildingGeometry(124.01),
            'properties' => ['code' => 'ZH'],
            'color' => '#123456',
        ]);

        $first = Building::create([
            'name' => 'Alpha Hall',
            'geometry' => $this->buildingGeometry(124.00),
            'properties' => ['code' => 'AH'],
            'color' => '#abcdef',
        ]);

        $response = $this->getJson('/api/buildings');

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.geometry.type', 'Polygon')
            ->assertJsonPath('0.properties.code', 'AH')
            ->assertJsonPath('0.color', '#abcdef')
            ->assertJsonPath('1.id', $second->id);
    }

    public function test_map_api_response_is_cached_until_wayfinding_cache_is_invalidated(): void
    {
        $building = Building::create([
            'name' => 'Original Name',
            'geometry' => $this->buildingGeometry(),
        ]);

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertHeader('X-Wayfinding-Cache', 'MISS')
            ->assertJsonPath('0.name', 'Original Name');

        $building->update(['name' => 'Updated Name']);

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertHeader('X-Wayfinding-Cache', 'HIT')
            ->assertJsonPath('0.name', 'Original Name');

        app(WayfindingCache::class)->invalidate();

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertHeader('X-Wayfinding-Cache', 'MISS')
            ->assertJsonPath('0.name', 'Updated Name');
    }

    public function test_successful_admin_mutation_invalidates_cached_map_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $building = Building::create([
            'name' => 'Old Building Name',
            'geometry' => $this->buildingGeometry(),
        ]);

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertHeader('X-Wayfinding-Cache', 'MISS')
            ->assertJsonPath('0.name', 'Old Building Name');

        $this
            ->actingAs($admin)
            ->patchJson(route('admin.buildings.updateName', $building), [
                'name' => 'New Building Name',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertHeader('X-Wayfinding-Cache', 'MISS')
            ->assertJsonPath('0.name', 'New Building Name');
    }

    public function test_destination_search_is_rate_limited(): void
    {
        $clientId = (string) Str::uuid();
        $this->withCredentials();

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->withUnencryptedCookie('wayfinding_client_id', $clientId)
                ->getJson('/api/search-destination')
                ->assertStatus(422);
        }

        $this->withUnencryptedCookie('wayfinding_client_id', $clientId)
            ->getJson('/api/search-destination')
            ->assertTooManyRequests();
    }

    public function test_destination_search_limits_devices_separately_on_one_network(): void
    {
        $firstClient = (string) Str::uuid();
        $secondClient = (string) Str::uuid();
        $this->withCredentials();

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->withUnencryptedCookie('wayfinding_client_id', $firstClient)
                ->getJson('/api/search-destination')
                ->assertStatus(422);
        }

        $this->withUnencryptedCookie('wayfinding_client_id', $firstClient)
            ->getJson('/api/search-destination')
            ->assertTooManyRequests();

        $this->withUnencryptedCookie('wayfinding_client_id', $secondClient)
            ->getJson('/api/search-destination')
            ->assertStatus(422);
    }

    public function test_paths_endpoint_exposes_geojson_and_routing_metadata(): void
    {
        $path = Path::create([
            'name' => 'Flood-prone Walkway',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [124.0000, 10.0000],
                    [124.0005, 10.0005],
                ],
            ],
            'type' => 'walkway',
            'risk_level' => 3,
            'difficulty_level' => 2,
            'is_blocked' => true,
            'hazard_note' => 'Flooding',
            'properties' => ['surface' => 'concrete'],
        ]);

        $response = $this->getJson('/api/paths');

        $response
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.type', 'Feature')
            ->assertJsonPath('features.0.geometry.type', 'LineString')
            ->assertJsonPath('features.0.properties.id', $path->id)
            ->assertJsonPath('features.0.properties.risk_level', 3)
            ->assertJsonPath('features.0.properties.difficulty_level', 2)
            ->assertJsonPath('features.0.properties.is_blocked', true)
            ->assertJsonPath('features.0.properties.properties.surface', 'concrete');
    }

    public function test_indoor_endpoints_include_floor_context_and_blocked_state(): void
    {
        $building = Building::create([
            'name' => 'Engineering Building',
            'geometry' => $this->buildingGeometry(),
        ]);

        $activeMap = IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Engineering First Floor',
            'floor_number' => 1,
            'floor_label' => null,
            'geometry' => $this->buildingGeometry(),
            'is_active' => true,
        ]);

        IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Inactive Floor',
            'floor_number' => 2,
            'is_active' => false,
        ]);

        $room = IndoorRoom::create([
            'indoor_map_id' => $activeMap->id,
            'name' => 'Robotics Laboratory',
            'room_code' => 'R101',
            'type' => 'laboratory',
            'geometry' => $this->buildingGeometry(124.0001),
            'properties' => ['accessible' => true],
        ]);

        $path = IndoorPath::create([
            'indoor_map_id' => $activeMap->id,
            'name' => 'Closed Hallway',
            'path_type' => 'hallway',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [124.0000, 10.0000],
                    [124.0001, 10.0001],
                ],
            ],
            'is_blocked' => true,
            'properties' => ['reason' => 'maintenance'],
        ]);

        $this->getJson('/api/indoor-maps')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $activeMap->id)
            ->assertJsonPath('0.floor_label', '1F');

        $this->getJson('/api/indoor-rooms')
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('features.0.properties.id', $room->id)
            ->assertJsonPath('features.0.properties.building_id', $building->id)
            ->assertJsonPath('features.0.properties.floor_number', 1)
            ->assertJsonPath('features.0.properties.floor_label', '1F');

        $this->getJson('/api/indoor-paths')
            ->assertOk()
            ->assertJsonPath('features.0.properties.id', $path->id)
            ->assertJsonPath('features.0.properties.is_blocked', true)
            ->assertJsonPath('features.0.properties.properties.reason', 'maintenance');
    }

    public function test_destination_search_requires_text_and_an_active_keyword(): void
    {
        $this->getJson('/api/search-destination')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Search text is required.');

        $building = Building::create([
            'name' => 'Library',
            'geometry' => $this->buildingGeometry(),
        ]);

        DestinationKeyword::create([
            'keyword' => 'library',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 10,
            'is_active' => false,
        ]);

        $this->getJson('/api/search-destination?q=library')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No active destination keywords found. Please add keywords in admin first.');
    }

    public function test_destination_search_resolves_a_room_within_building_context(): void
    {
        $building = Building::create([
            'name' => 'Engineering Building',
            'geometry' => $this->buildingGeometry(),
        ]);

        $map = IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Engineering Second Floor',
            'floor_number' => 2,
            'floor_label' => '2F',
            'is_active' => true,
        ]);

        $room = IndoorRoom::create([
            'indoor_map_id' => $map->id,
            'name' => 'Computer Laboratory',
            'room_code' => '201',
            'type' => 'laboratory',
            'geometry' => $this->buildingGeometry(124.0002),
        ]);

        DestinationKeyword::create([
            'keyword' => 'engineering',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 5,
            'is_active' => true,
        ]);

        DestinationKeyword::create([
            'keyword' => '201',
            'destination_type' => 'room',
            'destination_id' => $room->id,
            'priority' => 10,
            'is_active' => true,
        ]);

        $this->getJson('/api/search-destination?q=palihog%20adto%20ko%20sa%20engineering%20room%20201')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('match_type', 'room')
            ->assertJsonPath('result.destination_id', $room->id)
            ->assertJsonPath('result.building_id', $building->id)
            ->assertJsonPath('result.floor_number', 2)
            ->assertJsonPath('result.room_code', '201');
    }

    public function test_destination_search_rejects_a_room_outside_the_detected_building(): void
    {
        $requestedBuilding = Building::create([
            'name' => 'Engineering Building',
            'geometry' => $this->buildingGeometry(),
        ]);

        $otherBuilding = Building::create([
            'name' => 'Science Building',
            'geometry' => $this->buildingGeometry(124.01),
        ]);

        $otherMap = IndoorMap::create([
            'building_id' => $otherBuilding->id,
            'name' => 'Science First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'is_active' => true,
        ]);

        $otherRoom = IndoorRoom::create([
            'indoor_map_id' => $otherMap->id,
            'name' => 'Room 201',
            'room_code' => '201',
            'geometry' => $this->buildingGeometry(124.0101),
        ]);

        DestinationKeyword::create([
            'keyword' => 'engineering',
            'destination_type' => 'building',
            'destination_id' => $requestedBuilding->id,
            'is_active' => true,
        ]);

        DestinationKeyword::create([
            'keyword' => '201',
            'destination_type' => 'room',
            'destination_id' => $otherRoom->id,
            'is_active' => true,
        ]);

        $this->getJson('/api/search-destination?q=engineering%20room%20201')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A room keyword matched, but it is not under the detected building keyword.');
    }

    public function test_campus_events_only_return_active_current_or_upcoming_events(): void
    {
        Carbon::setTestNow('2026-07-25 10:00:00');

        $building = Building::create([
            'name' => 'Activity Center',
            'geometry' => $this->buildingGeometry(),
        ]);

        CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'Happening Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
            'priority' => 5,
        ]);

        CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'High Priority Upcoming',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'is_active' => true,
            'priority' => 10,
        ]);

        CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'Already Ended',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'Inactive Upcoming',
            'starts_at' => now()->addDay(),
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/campus-events');

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.title', 'High Priority Upcoming')
            ->assertJsonPath('0.status', 'upcoming')
            ->assertJsonPath('1.title', 'Happening Now')
            ->assertJsonPath('1.status', 'happening_now')
            ->assertJsonMissing(['title' => 'Already Ended'])
            ->assertJsonMissing(['title' => 'Inactive Upcoming']);
    }

    private function buildingGeometry(float $longitude = 124.0): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$longitude, 10.0000],
                [$longitude + 0.0001, 10.0000],
                [$longitude + 0.0001, 10.0001],
                [$longitude, 10.0001],
                [$longitude, 10.0000],
            ]],
        ];
    }
}
