<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HazardPointMapBuildingLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_hazard_map_displays_buildings_as_a_visual_layer_without_a_building_form_field(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        Building::create([
            'name' => 'Visible Academic Building',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.1, 10.0], [124.1, 10.1], [124.0, 10.0]]],
            ],
            'color' => '#2b82cc',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.hazard-point'))
            ->assertOk()
            ->assertSee('const buildingFeatures =', false)
            ->assertSee('Visible Academic Building')
            ->assertSee('hazard-building-shape')
            ->assertDontSee('name="building_id"', false);
    }
}
