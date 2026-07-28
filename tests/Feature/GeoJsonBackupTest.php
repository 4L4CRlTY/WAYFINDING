<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\IndoorMap;
use App\Models\User;
use App\Services\GeoJsonBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class GeoJsonBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $sourceRoot;

    private string $exactBuildingsContent;

    private string $exactFloorplanContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wayfinding-backup-test-'.Str::uuid();
        $this->exactBuildingsContent = <<<'GEOJSON'
{
  "type": "FeatureCollection",
  "custom_spacing": "must remain byte-for-byte",
  "features": []
}
GEOJSON;
        $this->exactFloorplanContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        File::makeDirectory($this->sourceRoot.DIRECTORY_SEPARATOR.'Buildings', 0755, true);
        File::makeDirectory(
            $this->sourceRoot.DIRECTORY_SEPARATOR.'indoor_rooms'.DIRECTORY_SEPARATOR.'information_technology'.DIRECTORY_SEPARATOR.'1f',
            0755,
            true,
        );
        File::makeDirectory($this->sourceRoot.DIRECTORY_SEPARATOR.'floorplan_image', 0755, true);

        File::put(
            $this->sourceRoot.DIRECTORY_SEPARATOR.'Buildings'.DIRECTORY_SEPARATOR.'buildings.geojson',
            $this->exactBuildingsContent,
        );
        File::put(
            $this->sourceRoot.DIRECTORY_SEPARATOR.'indoor_rooms'.DIRECTORY_SEPARATOR.'information_technology'.DIRECTORY_SEPARATOR.'1f'.DIRECTORY_SEPARATOR.'indoor_rooms.geojson',
            '{"type":"FeatureCollection","features":[]}',
        );
        File::put(
            $this->sourceRoot.DIRECTORY_SEPARATOR.'floorplan_image'.DIRECTORY_SEPARATOR.'information_technology_floor_1.png',
            $this->exactFloorplanContent,
        );

        $building = Building::create([
            'name' => 'Information Technology',
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

        IndoorMap::create([
            'building_id' => $building->id,
            'name' => 'Information Technology - 1F',
            'floor_number' => 1,
            'floor_label' => '1F',
            'floorplan_image' => 'information_technology_floor_1.png',
            'width' => 1,
            'height' => 1,
            'geometry' => null,
            'is_active' => true,
        ]);

        $this->app->instance(
            GeoJsonBackupService::class,
            new GeoJsonBackupService($this->sourceRoot),
        );
    }

    protected function tearDown(): void
    {
        if (
            isset($this->sourceRoot)
            && str_starts_with($this->sourceRoot, sys_get_temp_dir().DIRECTORY_SEPARATOR.'wayfinding-backup-test-')
            && File::isDirectory($this->sourceRoot)
        ) {
            File::deleteDirectory($this->sourceRoot);
        }

        parent::tearDown();
    }

    public function test_admin_can_open_exact_file_backup_center(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.geojson-backups.index'));

        $response
            ->assertOk()
            ->assertSeeText('Map & Floorplan Backup Center')
            ->assertSeeText('Outdoor Source Files')
            ->assertSeeText('Information Technology')
            ->assertSeeText('Floorplan Image')
            ->assertSeeText('Download Complete Backup')
            ->assertSeeText('Buildings/buildings.geojson')
            ->assertSeeText('1F')
            ->assertSeeText('Download Exact File');
    }

    public function test_individual_download_is_the_exact_stored_file_without_reformatting(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $sourceFile = $this->sourceRoot.DIRECTORY_SEPARATOR.'Buildings'.DIRECTORY_SEPARATOR.'buildings.geojson';
        $backupFile = app(GeoJsonBackupService::class)
            ->files()
            ->firstWhere('relative_path', 'Buildings/buildings.geojson');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.geojson-backups.download', ['dataset' => $backupFile['id']]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/geo+json')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertDownload('buildings.geojson');

        $downloadedPath = $response->baseResponse->getFile()->getPathname();

        $this->assertSame(hash_file('sha256', $sourceFile), hash_file('sha256', $downloadedPath));
        $this->assertSame($this->exactBuildingsContent, File::get($downloadedPath));
    }

    public function test_complete_archive_preserves_exact_bytes_and_original_directory_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.geojson-backups.download-all'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/x-tar')
            ->assertDownload();

        $archivePath = $response->baseResponse->getFile()->getPathname();
        $archive = new \PharData($archivePath);

        $this->assertSame(
            $this->exactBuildingsContent,
            $archive['original_files/Buildings/buildings.geojson']->getContent(),
        );
        $this->assertTrue(isset(
            $archive['original_files/indoor_rooms/information_technology/1f/indoor_rooms.geojson'],
        ));
        $this->assertSame(
            $this->exactFloorplanContent,
            $archive['original_files/floorplan_image/information_technology_floor_1.png']->getContent(),
        );

        unset($archive);
        @unlink($archivePath);
    }

    public function test_floorplan_download_is_the_exact_original_image_without_recompression(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $sourceFile = $this->sourceRoot.DIRECTORY_SEPARATOR.'floorplan_image'.DIRECTORY_SEPARATOR.'information_technology_floor_1.png';
        $backupFile = app(GeoJsonBackupService::class)
            ->files()
            ->firstWhere('relative_path', 'floorplan_image/information_technology_floor_1.png');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.geojson-backups.download', ['dataset' => $backupFile['id']]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertDownload('information_technology_floor_1.png');

        $downloadedPath = $response->baseResponse->getFile()->getPathname();

        $this->assertSame(hash_file('sha256', $sourceFile), hash_file('sha256', $downloadedPath));
        $this->assertSame($this->exactFloorplanContent, File::get($downloadedPath));
    }

    public function test_unknown_file_identifier_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);

        $this
            ->actingAs($admin)
            ->get(route('admin.geojson-backups.download', ['dataset' => str_repeat('0', 64)]))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_access_backup_center_or_downloads(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
            'authorized_permissions' => ['buildings'],
        ]);
        $backupFile = app(GeoJsonBackupService::class)->files()->first();

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.geojson-backups.index'))
            ->assertForbidden();

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.geojson-backups.download', ['dataset' => $backupFile['id']]))
            ->assertForbidden();
    }
}
