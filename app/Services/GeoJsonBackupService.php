<?php

namespace App\Services;

use App\Models\Building;
use App\Models\IndoorMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GeoJsonBackupService
{
    public function __construct(
        private readonly ?string $sourceRoot = null,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function files(): Collection
    {
        return collect([
            ...$this->outdoorFiles(),
            ...$this->indoorFiles(),
            ...$this->indoorImageFiles(),
        ])
            ->unique('relative_path')
            ->sortBy(fn (array $file): string => $file['group'].'|'.$file['category'].'|'.$file['relative_path'])
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        return $this->files()->first(
            fn (array $file): bool => hash_equals($file['id'], $id),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function outdoorFiles(): array
    {
        $definitions = [
            [
                'relative_path' => 'Buildings/buildings.geojson',
                'category' => 'Buildings',
                'description' => 'Exact current buildings upload.',
                'icon' => 'ri-building-2-line',
            ],
            [
                'relative_path' => 'Paths/path.geojson',
                'category' => 'Outdoor Paths',
                'description' => 'Exact current outdoor paths upload.',
                'icon' => 'ri-route-line',
            ],
            [
                'relative_path' => 'EntryPoints/entry_points.geojson',
                'category' => 'Entry Points',
                'description' => 'Exact current entry points upload.',
                'icon' => 'ri-map-pin-add-line',
            ],
            [
                'relative_path' => 'Landuses/landuse.geojson',
                'category' => 'Land Use',
                'description' => 'Exact current land-use upload.',
                'icon' => 'ri-landscape-line',
            ],
        ];

        return collect($definitions)
            ->filter(fn (array $definition): bool => File::isFile($this->path($definition['relative_path'])))
            ->map(fn (array $definition): array => $this->describeFile(
                absolutePath: $this->path($definition['relative_path']),
                relativePath: $definition['relative_path'],
                group: 'Outdoor Source Files',
                category: $definition['category'],
                description: $definition['description'],
                icon: $definition['icon'],
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function indoorFiles(): array
    {
        $buildingNames = Building::query()
            ->pluck('name')
            ->mapWithKeys(fn (?string $name): array => [
                Str::slug($name ?: 'building', '_') => $name ?: 'Building',
            ]);

        $definitions = [
            'indoor_maps' => [
                'filename' => 'indoor_map.geojson',
                'category' => 'Indoor Map Floor Extents',
                'description' => 'Exact uploaded indoor floor-extent file.',
                'icon' => 'ri-map-2-line',
            ],
            'indoor_rooms' => [
                'filename' => 'indoor_rooms.geojson',
                'category' => 'Indoor Rooms',
                'description' => 'Exact uploaded indoor rooms file.',
                'icon' => 'ri-door-line',
            ],
            'indoor_paths' => [
                'filename' => 'indoor_paths.geojson',
                'category' => 'Indoor Paths',
                'description' => 'Exact uploaded indoor paths file.',
                'icon' => 'ri-route-line',
            ],
            'indoor_entrances' => [
                'filename' => 'indoor_entrances.geojson',
                'category' => 'Indoor Entrances',
                'description' => 'Exact uploaded indoor entrances file.',
                'icon' => 'ri-door-open-line',
            ],
        ];

        $sourceFiles = [];

        foreach ($definitions as $root => $definition) {
            $absoluteRoot = $this->path($root);

            if (! File::isDirectory($absoluteRoot)) {
                continue;
            }

            foreach (File::allFiles($absoluteRoot) as $file) {
                if (strcasecmp($file->getFilename(), $definition['filename']) !== 0) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', Str::after($file->getPathname(), $this->root().DIRECTORY_SEPARATOR));
                $parts = explode('/', $relativePath);
                $floor = $parts[2] ?? 'floor';
                $contentHash = hash_file('sha256', $file->getPathname());
                $sourceFiles[] = [
                    'absolute_path' => $file->getPathname(),
                    'relative_path' => $relativePath,
                    'building_slug' => $parts[1] ?? 'building',
                    'floor' => $floor,
                    'match_key' => $root.'|'.$floor.'|'.$contentHash,
                    'definition' => $definition,
                ];
            }
        }

        $canonicalNamesByHash = collect($sourceFiles)
            ->filter(fn (array $file): bool => $buildingNames->has($file['building_slug']))
            ->mapWithKeys(fn (array $file): array => [
                $file['match_key'] => $buildingNames->get($file['building_slug']),
            ]);

        return collect($sourceFiles)
            ->map(function (array $file) use ($buildingNames, $canonicalNamesByHash): array {
                $buildingLabel = $buildingNames->get($file['building_slug'])
                    ?? $canonicalNamesByHash->get($file['match_key'])
                    ?? Str::headline($file['building_slug']);
                $floorLabel = Str::upper($file['floor']);
                $definition = $file['definition'];

                return $this->describeFile(
                    absolutePath: $file['absolute_path'],
                    relativePath: $file['relative_path'],
                    group: 'Indoor Building Files',
                    category: $definition['category'],
                    description: $definition['description'],
                    icon: $definition['icon'],
                    location: $buildingLabel.' - '.$floorLabel,
                    buildingLabel: $buildingLabel,
                    floorLabel: $floorLabel,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function indoorImageFiles(): array
    {
        $files = [];

        foreach (IndoorMap::query()->with('building')->orderBy('id')->get() as $map) {
            $buildingLabel = $map->building?->name ?: 'Building';
            $floorLabel = $map->floor_label ?: ($map->floor_number.'F');

            foreach ([
                ['filename' => $map->floorplan_image, 'category' => 'Floorplan Image', 'description' => 'Exact current indoor floorplan image.'],
                ['filename' => $map->backup_floorplan_image, 'category' => 'Previous Floorplan Image', 'description' => 'Exact previous indoor floorplan image.'],
            ] as $image) {
                if (! is_string($image['filename']) || trim($image['filename']) === '') {
                    continue;
                }

                $relativePath = 'floorplan_image/'.basename($image['filename']);
                $absolutePath = $this->path($relativePath);

                if (! File::isFile($absolutePath)) {
                    continue;
                }

                $files[] = $this->describeFile(
                    absolutePath: $absolutePath,
                    relativePath: $relativePath,
                    group: 'Indoor Building Files',
                    category: $image['category'],
                    description: $image['description'],
                    icon: 'ri-image-2-line',
                    location: $buildingLabel.' - '.$floorLabel,
                    buildingLabel: $buildingLabel,
                    floorLabel: $floorLabel,
                );
            }
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeFile(
        string $absolutePath,
        string $relativePath,
        string $group,
        string $category,
        string $description,
        string $icon,
        ?string $location = null,
        ?string $buildingLabel = null,
        ?string $floorLabel = null,
    ): array {
        $normalizedRelativePath = str_replace('\\', '/', $relativePath);
        $modifiedAt = File::lastModified($absolutePath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return [
            'id' => hash('sha256', $normalizedRelativePath),
            'absolute_path' => $absolutePath,
            'relative_path' => $normalizedRelativePath,
            'filename' => basename($absolutePath),
            'group' => $group,
            'category' => $category,
            'description' => $description,
            'icon' => $icon,
            'location' => $location,
            'building_label' => $buildingLabel,
            'building_key' => $buildingLabel ? Str::slug($buildingLabel, '_') : null,
            'floor_label' => $floorLabel,
            'file_kind' => $extension === 'geojson' ? 'geojson' : 'image',
            'mime_type' => $extension === 'geojson'
                ? 'application/geo+json'
                : (File::mimeType($absolutePath) ?: $this->fallbackMimeType($absolutePath)),
            'size' => File::size($absolutePath),
            'size_label' => $this->formatBytes(File::size($absolutePath)),
            'modified_at' => date('Y-m-d H:i:s', $modifiedAt),
        ];
    }

    private function root(): string
    {
        return rtrim($this->sourceRoot ?? public_path(), '\\/');
    }

    private function path(string $relativePath): string
    {
        return $this->root().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }

    private function fallbackMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'geojson', 'json' => 'application/geo+json',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
