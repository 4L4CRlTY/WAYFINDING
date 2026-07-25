<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidGeoJson implements ValidationRule
{
    /**
     * @param  array<int, string>  $allowedGeometryTypes
     */
    public function __construct(
        private readonly array $allowedGeometryTypes,
        private readonly bool $requireFeatureCollection = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid GeoJSON file.');

            return;
        }

        $decoded = json_decode((string) file_get_contents($value->getRealPath()), true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            $fail('The :attribute contains invalid JSON.');

            return;
        }

        $geometries = $this->extractGeometries($decoded);

        if ($geometries === null || $geometries === []) {
            $fail($this->requireFeatureCollection
                ? 'The :attribute must be a non-empty GeoJSON FeatureCollection.'
                : 'The :attribute must contain a valid GeoJSON geometry.');

            return;
        }

        foreach ($geometries as $index => $geometry) {
            $type = $geometry['type'] ?? null;

            if (! is_string($type) || ! in_array($type, $this->allowedGeometryTypes, true)) {
                $allowed = implode(', ', $this->allowedGeometryTypes);
                $fail("Feature {$index} must use one of these geometry types: {$allowed}.");

                return;
            }

            if (! $this->hasValidCoordinates($type, $geometry['coordinates'] ?? null)) {
                $fail("Feature {$index} has invalid coordinates for {$type}.");

                return;
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function extractGeometries(array $geoJson): ?array
    {
        $type = $geoJson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            $features = $geoJson['features'] ?? null;

            if (! is_array($features) || ! array_is_list($features)) {
                return null;
            }

            $geometries = [];

            foreach ($features as $feature) {
                if (
                    ! is_array($feature)
                    || ($feature['type'] ?? null) !== 'Feature'
                    || ! is_array($feature['geometry'] ?? null)
                ) {
                    return null;
                }

                $geometries[] = $feature['geometry'];
            }

            return $geometries;
        }

        if ($this->requireFeatureCollection) {
            return null;
        }

        if ($type === 'Feature' && is_array($geoJson['geometry'] ?? null)) {
            return [$geoJson['geometry']];
        }

        if (is_string($type) && array_key_exists('coordinates', $geoJson)) {
            return [$geoJson];
        }

        return null;
    }

    private function hasValidCoordinates(string $type, mixed $coordinates): bool
    {
        return match ($type) {
            'Point' => $this->isPosition($coordinates),
            'LineString' => $this->isLineString($coordinates),
            'MultiLineString' => $this->isListOf($coordinates, fn ($line) => $this->isLineString($line)),
            'Polygon' => $this->isPolygon($coordinates),
            'MultiPolygon' => $this->isListOf($coordinates, fn ($polygon) => $this->isPolygon($polygon)),
            default => false,
        };
    }

    private function isPosition(mixed $position): bool
    {
        if (
            ! is_array($position)
            || count($position) < 2
            || ! is_numeric($position[0])
            || ! is_numeric($position[1])
        ) {
            return false;
        }

        $longitude = (float) $position[0];
        $latitude = (float) $position[1];

        return $longitude >= -180
            && $longitude <= 180
            && $latitude >= -90
            && $latitude <= 90;
    }

    private function isLineString(mixed $line): bool
    {
        return is_array($line)
            && array_is_list($line)
            && count($line) >= 2
            && collect($line)->every(fn ($position) => $this->isPosition($position));
    }

    private function isPolygon(mixed $polygon): bool
    {
        return $this->isListOf($polygon, function ($ring) {
            if (
                ! is_array($ring)
                || ! array_is_list($ring)
                || count($ring) < 4
                || ! collect($ring)->every(fn ($position) => $this->isPosition($position))
            ) {
                return false;
            }

            return $ring[0] === $ring[array_key_last($ring)];
        });
    }

    private function isListOf(mixed $items, Closure $validator): bool
    {
        return is_array($items)
            && array_is_list($items)
            && $items !== []
            && collect($items)->every($validator);
    }
}
