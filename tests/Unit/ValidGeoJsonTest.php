<?php

namespace Tests\Unit;

use App\Rules\ValidGeoJson;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidGeoJsonTest extends TestCase
{
    public function test_valid_linestring_feature_collection_is_accepted(): void
    {
        $file = $this->geoJsonFile([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [124.0, 10.0],
                        [124.001, 10.001],
                    ],
                ],
            ]],
        ]);

        $validator = Validator::make(
            ['geojson' => $file],
            ['geojson' => [new ValidGeoJson(['LineString', 'MultiLineString'])]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_wrong_geometry_type_is_rejected(): void
    {
        $file = $this->geoJsonFile([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [124.0, 10.0],
                ],
            ]],
        ]);

        $validator = Validator::make(
            ['geojson' => $file],
            ['geojson' => [new ValidGeoJson(['Polygon', 'MultiPolygon'])]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_direct_polygon_geometry_is_accepted_for_indoor_map_uploads(): void
    {
        $file = $this->geoJsonFile([
            'type' => 'Polygon',
            'coordinates' => [[
                [124.0, 10.0],
                [124.001, 10.0],
                [124.001, 10.001],
                [124.0, 10.0],
            ]],
        ]);

        $validator = Validator::make(
            ['geojson' => $file],
            ['geojson' => [new ValidGeoJson(['Polygon', 'MultiPolygon'], false)]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_out_of_range_coordinates_are_rejected(): void
    {
        $file = $this->geoJsonFile([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [200.0, 95.0],
                ],
            ]],
        ]);

        $validator = Validator::make(
            ['geojson' => $file],
            ['geojson' => [new ValidGeoJson(['Point'])]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_polygon_ring_must_be_closed(): void
    {
        $file = $this->geoJsonFile([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [124.0, 10.0],
                        [124.001, 10.0],
                        [124.001, 10.001],
                        [124.0, 10.001],
                    ]],
                ],
            ]],
        ]);

        $validator = Validator::make(
            ['geojson' => $file],
            ['geojson' => [new ValidGeoJson(['Polygon'])]],
        );

        $this->assertTrue($validator->fails());
    }

    private function geoJsonFile(array $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'map.geojson',
            (string) json_encode($contents, JSON_THROW_ON_ERROR),
        );
    }
}
