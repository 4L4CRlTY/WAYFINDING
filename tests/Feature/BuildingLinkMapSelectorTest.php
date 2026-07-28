<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingLinkMapSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_indoor_stairs_link_page_offers_map_and_dropdown_building_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $building = $this->building('Information Technology');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.indoor-stairs-link'));

        $response
            ->assertOk()
            ->assertSee('data-building-map-selector', false)
            ->assertSee('data-building-selector-method="map"', false)
            ->assertSee('data-building-selector-method="dropdown"', false)
            ->assertSee('id="stairs_link_add_building_map"', false)
            ->assertSee('id="add_building_id"', false)
            ->assertSeeText($building->name);
    }

    public function test_building_entrance_link_page_offers_map_and_dropdown_building_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $building = $this->building('Administration Building');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.building-entrance-link'));

        $response
            ->assertOk()
            ->assertSee('data-building-map-selector', false)
            ->assertSee('data-building-selector-method="map"', false)
            ->assertSee('data-building-selector-method="dropdown"', false)
            ->assertSee('id="entrance_link_add_building_map"', false)
            ->assertSee('id="add_building_id"', false)
            ->assertSeeText($building->name);
    }

    private function building(string $name): Building
    {
        return Building::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [124.9980, 10.2920],
                    [124.9982, 10.2920],
                    [124.9982, 10.2922],
                    [124.9980, 10.2922],
                    [124.9980, 10.2920],
                ]],
            ],
            'properties' => [],
            'color' => '#4f94d4',
        ]);
    }
}
