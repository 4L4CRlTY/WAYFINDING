<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use App\Models\Path;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusEventMapSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_form_offers_clickable_map_and_dropdown_destination_selection(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $building = Building::create([
            'name' => 'Interactive Technology Center',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        $map = IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Technology First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'is_active' => true,
        ]);

        IndoorRoom::create([
            'indoor_map_id' => $map->id,
            'name' => 'Innovation Laboratory',
            'room_code' => 'IT-LAB-01',
            'type' => 'laboratory',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        Landuse::create([
            'name' => 'Campus Open Field',
            'properties' => ['type' => 'activity_area'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        Landuse::create([
            'name' => 'Decorative Landscape Only',
            'properties' => ['type' => 'design'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        Path::create([
            'name' => 'Technology Walkway',
            'type' => 'walkway',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.campus-event'))
            ->assertOk()
            ->assertSee('id="campusEventTargetMap"', false)
            ->assertSee('data-event-target-method="map"', false)
            ->assertSee('data-event-target-method="dropdown"', false)
            ->assertSeeText('Select on Map')
            ->assertSeeText('Use Dropdown')
            ->assertSeeText('Interactive Technology Center')
            ->assertSeeText('Innovation Laboratory')
            ->assertSeeText('Campus Open Field')
            ->assertDontSeeText('Decorative Landscape Only')
            ->assertSee('Technology Walkway', false);
    }

    public function test_design_landuse_cannot_be_submitted_as_an_event_destination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        $designLanduse = Landuse::create([
            'name' => 'Decorative Garden',
            'properties' => ['type' => 'design'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.campus-event'))
            ->post(route('admin.campus-event.store'), [
                'event_target_type' => 'landuse',
                'landuse_id' => $designLanduse->id,
                'title' => 'Invalid Design Event',
                'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.campus-event'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('campus_events', [
            'title' => 'Invalid Design Event',
        ]);
        $this->assertDatabaseCount('destination_links', 0);
    }
}
