<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\DestinationKeyword;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationKeywordMapSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_page_offers_map_dropdown_and_building_group_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $building = $this->building('Information Technology');
        $room = $this->room($building, 'IT-103', 'Computer Laboratory 1');
        $field = $this->landuse('Open Field', 'field');
        $design = $this->landuse('Decorative Campus Border', 'design');

        DestinationKeyword::create([
            'keyword' => 'Computer',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        DestinationKeyword::create([
            'keyword' => 'Lab One',
            'destination_type' => 'room',
            'destination_id' => $room->id,
            'priority' => 2,
            'is_active' => true,
        ]);

        DestinationKeyword::create([
            'keyword' => 'Activity Field',
            'destination_type' => 'landuse',
            'destination_id' => $field->id,
            'priority' => 1,
            'is_active' => true,
        ]);

        DestinationKeyword::create([
            'keyword' => 'Hidden Decoration',
            'destination_type' => 'landuse',
            'destination_id' => $design->id,
            'priority' => 1,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.destination-keyword'));

        $response
            ->assertOk()
            ->assertSee('id="destinationKeywordMap"', false)
            ->assertSee('data-dk-method="map"', false)
            ->assertSee('data-dk-method="dropdown"', false)
            ->assertSee('Keyword Directory by Building')
            ->assertSee('destination_group=building%3A'.$building->id, false)
            ->assertSeeText('Information Technology')
            ->assertSeeText('Open Field')
            ->assertDontSeeText('Decorative Campus Border')
            ->assertDontSeeText('Hidden Decoration');
    }

    public function test_building_group_contains_building_and_room_keywords_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $itBuilding = $this->building('Information Technology');
        $adminBuilding = $this->building('Administration Building');
        $itRoom = $this->room($itBuilding, 'IT-101', 'IT Office');
        $adminRoom = $this->room($adminBuilding, 'ADM-101', 'Records Office');

        foreach ([
            ['IT Building Alias', 'building', $itBuilding->id],
            ['IT Office Alias', 'room', $itRoom->id],
            ['Admin Room Alias', 'room', $adminRoom->id],
        ] as [$keyword, $type, $id]) {
            DestinationKeyword::create([
                'keyword' => $keyword,
                'destination_type' => $type,
                'destination_id' => $id,
                'priority' => 1,
                'is_active' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.destination-keyword', [
                'destination_group' => 'building:'.$itBuilding->id,
            ]))
            ->assertOk();

        preg_match('/<tbody>(.*?)<\/tbody>/s', $response->getContent(), $matches);
        $tableBody = $matches[1] ?? '';

        $this->assertStringContainsString('IT Building Alias', $tableBody);
        $this->assertStringContainsString('IT Office Alias', $tableBody);
        $this->assertStringNotContainsString('Admin Room Alias', $tableBody);
        $this->assertStringContainsString('IT-101', $tableBody);
        $this->assertStringContainsString('Information Technology', $tableBody);
    }

    public function test_design_landuse_cannot_receive_manual_keywords(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $design = $this->landuse('Decorative Campus Border', 'design');

        $this
            ->actingAs($admin)
            ->post(route('admin.destination-keyword.store'), [
                'destination_type' => 'landuse',
                'destination_id' => $design->id,
                'keywords' => 'hidden decoration',
                'priority' => 1,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('destination_keywords', [
            'destination_type' => 'landuse',
            'destination_id' => $design->id,
        ]);
    }

    private function building(string $name): Building
    {
        return Building::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.01, 10.0], [124.01, 10.01], [124.0, 10.0]]],
            ],
            'color' => '#68a7ee',
        ]);
    }

    private function room(Building $building, string $code, string $name): IndoorRoom
    {
        $map = IndoorMap::create([
            'building_id' => $building->id,
            'name' => $building->name.' 1F',
            'floor_number' => 1,
            'floor_label' => '1F',
            'is_active' => true,
        ]);

        return IndoorRoom::create([
            'indoor_map_id' => $map->id,
            'room_code' => $code,
            'name' => $name,
            'type' => 'room',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]],
            ],
        ]);
    }

    private function landuse(string $name, string $type): Landuse
    {
        return Landuse::create([
            'name' => $name,
            'properties' => ['type' => $type],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.01, 10.0], [124.01, 10.01], [124.0, 10.0]]],
            ],
        ]);
    }
}
