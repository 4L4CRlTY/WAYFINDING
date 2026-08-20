<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\DestinationKeyword;
use App\Models\IndoorEntrance;
use App\Models\IndoorMap;
use App\Models\IndoorPath;
use App\Models\IndoorStairLink;
use App\Services\CampusSnapshotPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CampusSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private ?string $snapshotPath = null;

    private ?string $searchIndexPath = null;

    private ?string $indoorSnapshotDirectory = null;

    protected function tearDown(): void
    {
        if ($this->snapshotPath) {
            File::delete($this->snapshotPath);
        }
        if ($this->searchIndexPath) {
            File::delete($this->searchIndexPath);
        }
        if ($this->indoorSnapshotDirectory) {
            File::deleteDirectory($this->indoorSnapshotDirectory);
        }

        parent::tearDown();
    }

    public function test_it_publishes_public_map_events_and_fuzzy_keyword_index(): void
    {
        $building = Building::create([
            'name' => 'Information Technology Building',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.1, 10.0], [124.1, 10.1], [124.0, 10.0]]],
            ],
            'properties' => ['code' => 'IT'],
            'color' => '#18375d',
        ]);

        DestinationKeyword::create([
            'keyword' => 'IT',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 10,
            'is_active' => true,
        ]);
        DestinationKeyword::create([
            'keyword' => 'Computer Building',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 5,
            'is_active' => true,
        ]);
        $indoorMap = IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'IT First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'floorplan_image' => 'it floor 1.png',
            'backup_floorplan_image' => 'it backup.png',
            'width' => 1200,
            'height' => 800,
            'geometry' => null,
            'is_active' => true,
        ]);
        IndoorPath::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Main Hallway',
            'path_type' => 'hallway',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [[124.0, 10.0], [124.01, 10.01]],
            ],
            'is_blocked' => false,
        ]);
        IndoorEntrance::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Main Indoor Entrance',
            'ent_type' => 'main',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [124.0, 10.0],
            ],
        ]);
        CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'IT Orientation',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'is_active' => true,
            'priority' => 5,
        ]);

        $this->snapshotPath = storage_path('framework/testing/campus-snapshot.json');
        $this->searchIndexPath = storage_path('framework/testing/destination-keywords.json');
        $this->indoorSnapshotDirectory = storage_path('framework/testing/indoor');
        $result = app(CampusSnapshotPublisher::class)->publish($this->snapshotPath);
        $snapshot = json_decode(File::get($this->snapshotPath), true, flags: JSON_THROW_ON_ERROR);
        $searchIndex = json_decode(File::get($this->searchIndexPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $snapshot['schema_version']);
        $this->assertSame(10, $result['datasets']);
        $this->assertSame(1, $result['indoor_buildings']);
        $this->assertArrayHasKey('/api/buildings', $snapshot['datasets']);
        $this->assertArrayNotHasKey('/api/indoor-paths', $snapshot['datasets']);
        $this->assertArrayNotHasKey('/api/indoor-entrances', $snapshot['datasets']);
        $this->assertArrayNotHasKey('/api/indoor-stairs-links', $snapshot['datasets']);
        $this->assertArrayHasKey('/api/campus-events', $snapshot['datasets']);
        $this->assertSame('IT Orientation', $snapshot['datasets']['/api/campus-events'][0]['title']);
        $this->assertSame('Information Technology Building', $snapshot['datasets']['/api/buildings'][0]['name']);
        $this->assertSame(
            '/floorplan_image/it%20floor%201.png',
            $snapshot['datasets']['/api/indoor-maps'][0]['floorplan_image']
        );
        $this->assertSame(
            '/floorplan_image/it%20backup.png',
            $snapshot['datasets']['/api/indoor-maps'][0]['backup_floorplan_image']
        );
        $this->assertSame('/data/destination-keywords.json', $snapshot['search_index_url']);
        $this->assertSame('/data/indoor/{building}.json', $snapshot['indoor_data_url_template']);
        $indoorSnapshot = json_decode(
            File::get($this->indoorSnapshotDirectory.DIRECTORY_SEPARATOR.$building->id.'.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertSame($building->id, $indoorSnapshot['building_id']);
        $this->assertArrayHasKey('/api/indoor-paths', $indoorSnapshot['datasets']);
        $this->assertArrayHasKey('/api/indoor-entrances', $indoorSnapshot['datasets']);
        $this->assertArrayHasKey('/api/indoor-stairs-links', $indoorSnapshot['datasets']);
        $this->assertCount(1, $indoorSnapshot['datasets']['/api/indoor-paths']['features']);
        $this->assertCount(1, $indoorSnapshot['datasets']['/api/indoor-entrances']['features']);
        $this->assertArrayNotHasKey('search_index', $snapshot);
        $this->assertSame(2, $searchIndex['schema_version']);
        $this->assertSame('compact-v1', $searchIndex['format']);
        $this->assertCount(1, $searchIndex['destinations']);
        $this->assertCount(2, $searchIndex['search_index']);
        $this->assertSame([0, $building->id, 'Information Technology Building', null, null, null, null, null], $searchIndex['destinations'][0]);
        $this->assertSame('IT', $searchIndex['search_index'][0][1]);
        $this->assertSame(0, $searchIndex['search_index'][0][2]);
        $this->assertSame(10, $searchIndex['search_index'][0][3]);
        $this->assertSame('Computer Building', $searchIndex['search_index'][1][1]);
        $this->assertSame(0, $searchIndex['search_index'][1][2]);
        $this->assertSame(5, $searchIndex['search_index'][1][3]);
        $this->assertArrayNotHasKey('users', $snapshot);
    }

    public function test_inactive_and_missing_destination_keywords_are_excluded(): void
    {
        DestinationKeyword::create([
            'keyword' => 'Inactive',
            'destination_type' => 'building',
            'destination_id' => 999,
            'priority' => 0,
            'is_active' => false,
        ]);
        DestinationKeyword::create([
            'keyword' => 'Missing',
            'destination_type' => 'building',
            'destination_id' => 999,
            'priority' => 0,
            'is_active' => true,
        ]);

        $this->snapshotPath = storage_path('framework/testing/campus-snapshot-filtered.json');
        $this->searchIndexPath = storage_path('framework/testing/destination-keywords.json');
        app(CampusSnapshotPublisher::class)->publish($this->snapshotPath);
        $searchIndex = json_decode(File::get($this->searchIndexPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([], $searchIndex['search_index']);
        $this->assertSame([], $searchIndex['destinations']);
    }

    public function test_snapshot_ignores_the_active_admin_building_filter(): void
    {
        $building = Building::create([
            'name' => 'Administration Building',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.1, 10.0], [124.1, 10.1], [124.0, 10.0]]],
            ],
            'properties' => ['code' => 'ADMIN'],
            'color' => '#18375d',
        ]);
        $indoorMap = IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Admin First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'floorplan_image' => 'admin-floor-1.png',
            'width' => 1200,
            'height' => 800,
            'geometry' => null,
            'is_active' => true,
        ]);
        IndoorPath::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Main Hallway',
            'path_type' => 'hallway',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [[124.0, 10.0], [124.01, 10.01]],
            ],
            'is_blocked' => false,
        ]);
        $firstEntrance = IndoorEntrance::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Main Entrance',
            'ent_type' => 'main',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [124.0, 10.0],
            ],
        ]);
        $secondEntrance = IndoorEntrance::create([
            'indoor_map_id' => $indoorMap->id,
            'name' => 'Upper Stair Landing',
            'ent_type' => 'stairs',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [124.01, 10.01],
            ],
        ]);
        IndoorStairLink::create([
            'building_id' => $building->id,
            'from_entrance_id' => $firstEntrance->id,
            'to_entrance_id' => $secondEntrance->id,
            'name' => 'Main Stairs',
        ]);

        $this->app->instance('request', Request::create(
            '/admin/campus-events',
            'POST',
            ['building_id' => 999]
        ));
        $this->snapshotPath = storage_path('framework/testing/campus-snapshot-request-filter.json');
        $this->searchIndexPath = storage_path('framework/testing/destination-keywords.json');
        $this->indoorSnapshotDirectory = storage_path('framework/testing/indoor');

        app(CampusSnapshotPublisher::class)->publish($this->snapshotPath);

        $indoorSnapshot = json_decode(
            File::get($this->indoorSnapshotDirectory.DIRECTORY_SEPARATOR.$building->id.'.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertCount(1, $indoorSnapshot['datasets']['/api/indoor-paths']['features']);
        $this->assertCount(2, $indoorSnapshot['datasets']['/api/indoor-entrances']['features']);
        $this->assertCount(1, $indoorSnapshot['datasets']['/api/indoor-stairs-links']);
    }

    public function test_republishing_reflects_keyword_edits_deletes_and_event_deactivation(): void
    {
        $building = Building::create([
            'name' => 'Administration Building',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[124.0, 10.0], [124.1, 10.0], [124.1, 10.1], [124.0, 10.0]]],
            ],
            'properties' => ['code' => 'ADMIN'],
            'color' => '#18375d',
        ]);
        $keyword = DestinationKeyword::create([
            'keyword' => 'Admin',
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'priority' => 5,
            'is_active' => true,
        ]);
        $event = CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'title' => 'Enrollment',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->snapshotPath = storage_path('framework/testing/campus-snapshot-refresh.json');
        $this->searchIndexPath = storage_path('framework/testing/destination-keywords.json');
        $publisher = app(CampusSnapshotPublisher::class);
        $publisher->publish($this->snapshotPath);

        $keyword->update(['keyword' => 'Registrar']);
        $event->update(['is_active' => false]);
        $publisher->publish($this->snapshotPath);

        $snapshot = json_decode(File::get($this->snapshotPath), true, flags: JSON_THROW_ON_ERROR);
        $searchIndex = json_decode(File::get($this->searchIndexPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Registrar', $searchIndex['search_index'][0][1]);
        $this->assertSame([], $snapshot['datasets']['/api/campus-events']);

        $keyword->delete();
        $event->delete();
        $publisher->publish($this->snapshotPath);

        $searchIndex = json_decode(File::get($this->searchIndexPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([], $searchIndex['search_index']);
        $this->assertSame([], $searchIndex['destinations']);
    }
}
