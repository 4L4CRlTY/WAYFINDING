<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\DestinationKeyword;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Services\DestinationKeywordSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationKeywordSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_search_friendly_building_and_room_aliases_without_duplicates(): void
    {
        $itBuilding = $this->building('Information Technology');
        $adminBuilding = $this->building('Admin Building');
        $itMap = $this->indoorMap($itBuilding, '1F');
        $adminMap = $this->indoorMap($adminBuilding, '1F');

        $laboratory = $this->room($itMap, 'LABORATORY 1', 'it-103', 'classroom');
        $itOffice = $this->room($itMap, 'OFFICE', 'it-101', 'office');
        $adminOffice = $this->room($adminMap, 'OFFICE', 'admin-101', 'office');

        DestinationKeyword::create([
            'keyword' => 'Tech Hub',
            'destination_type' => 'building',
            'destination_id' => $itBuilding->id,
            'priority' => 3,
            'is_active' => true,
        ]);

        $result = app(DestinationKeywordSynchronizer::class)->sync();

        $this->assertGreaterThan(0, $result['created']);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Information Technology',
            'destination_type' => 'building',
            'destination_id' => $itBuilding->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'IT',
            'destination_type' => 'building',
            'destination_id' => $itBuilding->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Computer',
            'destination_type' => 'building',
            'destination_id' => $itBuilding->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'IT-103',
            'destination_type' => 'room',
            'destination_id' => $laboratory->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Lab 1',
            'destination_type' => 'room',
            'destination_id' => $laboratory->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Information Technology IT-101',
            'destination_type' => 'room',
            'destination_id' => $itOffice->id,
        ]);
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Admin Building ADMIN-101',
            'destination_type' => 'room',
            'destination_id' => $adminOffice->id,
        ]);

        $this->assertDatabaseMissing('destination_keywords', [
            'keyword' => 'OFFICE',
            'destination_type' => 'room',
            'destination_id' => $itOffice->id,
        ]);
        $this->assertDatabaseMissing('destination_keywords', [
            'keyword' => 'OFFICE',
            'destination_type' => 'room',
            'destination_id' => $adminOffice->id,
        ]);

        $this->getJson('/api/search-destination?q=computer')
            ->assertOk()
            ->assertJsonPath('match_type', 'building')
            ->assertJsonPath('result.destination_id', $itBuilding->id);

        $this->getJson('/api/search-destination?q=IT')
            ->assertOk()
            ->assertJsonPath('match_type', 'building')
            ->assertJsonPath('result.destination_id', $itBuilding->id);

        $this->getJson('/api/search-destination?q=computer%20laboratory%201')
            ->assertOk()
            ->assertJsonPath('match_type', 'room')
            ->assertJsonPath('result.destination_id', $laboratory->id);

        $keywordCount = DestinationKeyword::count();
        $secondResult = app(DestinationKeywordSynchronizer::class)->sync();

        $this->assertSame(0, $secondResult['created']);
        $this->assertSame($keywordCount, DestinationKeyword::count());
        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Tech Hub',
            'destination_type' => 'building',
            'destination_id' => $itBuilding->id,
        ]);
    }

    public function test_the_console_command_runs_the_same_safe_sync(): void
    {
        $this->building('Covered Court');

        $this->artisan('destination-keywords:sync')
            ->expectsOutputToContain('missing keyword(s) generated')
            ->assertSuccessful();

        $this->assertDatabaseHas('destination_keywords', [
            'keyword' => 'Gym',
            'destination_type' => 'building',
        ]);
    }

    private function building(string $name): Building
    {
        return Building::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.1, 10.0], [124.1, 10.1], [124.0, 10.0]]],
            ],
        ]);
    }

    private function indoorMap(Building $building, string $floorLabel): IndoorMap
    {
        return IndoorMap::create([
            'building_id' => $building->id,
            'name' => $building->name.' '.$floorLabel,
            'floor_number' => 1,
            'floor_label' => $floorLabel,
            'is_active' => true,
        ]);
    }

    private function room(IndoorMap $map, string $name, string $code, string $type): IndoorRoom
    {
        return IndoorRoom::create([
            'indoor_map_id' => $map->id,
            'name' => $name,
            'room_code' => $code,
            'type' => $type,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]],
            ],
        ]);
    }
}
