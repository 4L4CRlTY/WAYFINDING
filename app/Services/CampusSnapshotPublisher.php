<?php

namespace App\Services;

use App\Http\Controllers\ApiController;
use App\Models\Building;
use App\Models\DestinationKeyword;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use App\Support\WayfindingCache;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class CampusSnapshotPublisher
{
    public const PUBLIC_PATH = 'data/campus-snapshot.json';

    public const SEARCH_INDEX_PUBLIC_PATH = 'data/destination-keywords.json';

    /**
     * These endpoints contain public, shared campus data and do not depend on
     * the current user or request parameters.
     *
     * @var array<string, string>
     */
    private const DATASET_METHODS = [
        '/api/buildings' => 'buildings',
        '/api/paths' => 'paths',
        '/api/entry-points' => 'entryPoints',
        '/api/building-entrances' => 'buildingEntrances',
        '/api/hazard-points' => 'hazardPoints',
        '/api/landuses' => 'landuses',
        '/api/indoor-maps' => 'indoorMaps',
        '/api/indoor-rooms' => 'indoorRooms',
        '/api/indoor-paths' => 'indoorPaths',
        '/api/indoor-entrances' => 'indoorEntrances',
        '/api/building-entrance-links' => 'buildingEntranceLinks',
        '/api/indoor-stairs-links' => 'indoorStairsLinks',
    ];

    public function __construct(
        private readonly ApiController $apiController,
        private readonly Filesystem $files,
        private readonly WayfindingCache $cache,
    ) {}

    /**
     * Build and atomically replace the public snapshot.
     *
     * The existing API remains available, so a missing or temporarily invalid
     * snapshot can never prevent the map from loading.
     */
    public function publish(
        ?string $destinationPath = null,
        ?string $searchIndexDestinationPath = null,
    ): array {
        $datasets = [];

        foreach (self::DATASET_METHODS as $url => $method) {
            $response = $this->apiController->{$method}();

            if (! $response instanceof JsonResponse || ! $response->isSuccessful()) {
                throw new RuntimeException("Unable to build campus snapshot dataset [{$url}].");
            }

            $datasets[$url] = $response->getData(true);
        }

        $searchIndex = $this->buildSearchIndex();
        $generatedAt = now()->toIso8601String();
        $cacheVersion = $this->cache->version();
        $snapshot = [
            'schema_version' => 1,
            'cache_version' => $cacheVersion,
            'generated_at' => $generatedAt,
            'datasets' => $datasets,
            'search_index_url' => '/data/destination-keywords.json',
        ];
        $searchIndexDocument = [
            'schema_version' => 1,
            'cache_version' => $cacheVersion,
            'generated_at' => $generatedAt,
            'search_index' => $searchIndex,
        ];

        $json = json_encode(
            $snapshot,
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
        );
        $searchIndexJson = json_encode(
            $searchIndexDocument,
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $path = $destinationPath ?: public_path(self::PUBLIC_PATH);
        $searchIndexPath = $searchIndexDestinationPath
            ?: ($destinationPath
                ? dirname($destinationPath).DIRECTORY_SEPARATOR.'destination-keywords.json'
                : public_path(self::SEARCH_INDEX_PUBLIC_PATH));
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->ensureDirectoryExists(dirname($searchIndexPath));
        // Publish the dependency first. Browsers using the previous snapshot
        // can still use its endpoint fallback during this brief replacement.
        $this->files->replace($searchIndexPath, $searchIndexJson);
        $this->files->replace($path, $json);

        return [
            'path' => $path,
            'search_index_path' => $searchIndexPath,
            'cache_version' => $snapshot['cache_version'],
            'datasets' => count($datasets),
            'keywords' => count($searchIndex),
            'bytes' => strlen($json) + strlen($searchIndexJson),
            'snapshot_bytes' => strlen($json),
            'search_index_bytes' => strlen($searchIndexJson),
        ];
    }

    /**
     * Include only active keywords whose destination still exists. The browser
     * uses an entry only for a unique exact match; all ambiguous or fuzzy text
     * continues through the existing server-side search.
     */
    private function buildSearchIndex(): array
    {
        $keywords = DestinationKeyword::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByRaw('LENGTH(keyword) DESC')
            ->orderBy('id')
            ->get();

        $buildingIds = $keywords
            ->where('destination_type', 'building')
            ->pluck('destination_id')
            ->unique()
            ->values();
        $roomIds = $keywords
            ->where('destination_type', 'room')
            ->pluck('destination_id')
            ->unique()
            ->values();
        $landuseIds = $keywords
            ->where('destination_type', 'landuse')
            ->pluck('destination_id')
            ->unique()
            ->values();

        $buildings = Building::query()
            ->whereIn('id', $buildingIds)
            ->get()
            ->keyBy('id');
        $rooms = IndoorRoom::with('indoorMap.building')
            ->whereIn('id', $roomIds)
            ->get()
            ->keyBy('id');
        $landuses = Landuse::query()
            ->whereIn('id', $landuseIds)
            ->get()
            ->keyBy('id');

        return $keywords
            ->map(function (DestinationKeyword $keyword) use ($buildings, $rooms, $landuses) {
                $result = match ($keyword->destination_type) {
                    'building' => $this->buildingSearchResult($buildings->get($keyword->destination_id)),
                    'room' => $this->roomSearchResult($rooms->get($keyword->destination_id)),
                    'landuse' => $this->landuseSearchResult($landuses->get($keyword->destination_id)),
                    default => null,
                };

                if ($result === null) {
                    return null;
                }

                return [
                    'id' => (int) $keyword->id,
                    'keyword' => $keyword->keyword,
                    'destination_type' => $keyword->destination_type,
                    'destination_id' => (int) $keyword->destination_id,
                ];
            })
            ->filter(fn ($entry) => $entry !== null && $this->normalizeKeyword($entry['keyword']) !== '')
            ->values()
            ->all();
    }

    private function buildingSearchResult(?Building $building): ?array
    {
        if (! $building) {
            return null;
        }

        return [
            'destination_type' => 'building',
            'destination_id' => (int) $building->id,
            'label' => $building->name ?: 'Building',
        ];
    }

    private function roomSearchResult(?IndoorRoom $room): ?array
    {
        if (! $room || ! $room->indoorMap) {
            return null;
        }

        return [
            'destination_type' => 'room',
            'destination_id' => (int) $room->id,
            'label' => $room->name ?: ($room->room_code ?: 'Room / Office'),
            'room_code' => $room->room_code,
            'building_id' => $room->indoorMap->building_id,
            'building_name' => $room->indoorMap->building->name ?? null,
            'floor_number' => $room->indoorMap->floor_number,
            'floor_label' => $room->indoorMap->floor_label,
        ];
    }

    private function landuseSearchResult(?Landuse $landuse): ?array
    {
        if (! $landuse) {
            return null;
        }

        return [
            'destination_type' => 'landuse',
            'destination_id' => (int) $landuse->id,
            'label' => $landuse->name ?: 'Landuse Area',
        ];
    }

    private function normalizeKeyword(?string $value): string
    {
        $value = mb_strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9\s]/iu', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }
}
