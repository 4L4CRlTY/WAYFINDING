<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\DestinationKeyword;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use App\Models\Path;
use App\Services\DestinationKeywordSynchronizer;
use Illuminate\Http\Request;

class DestinationKeywordController extends Controller
{
    public function DestinationKeyword(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $buildings = Building::select('id', 'name', 'geometry', 'color')
            ->orderBy('name')
            ->get();

        $landuses = Landuse::select('id', 'name', 'geometry', 'properties')
            ->orderBy('name')
            ->get()
            ->reject(fn (Landuse $landuse) => $this->isDesignLanduse($landuse))
            ->values();

        $rooms = IndoorRoom::with('indoorMap.building')
            ->orderBy('room_code')
            ->orderBy('name')
            ->get();

        $paths = Path::select('id', 'name', 'type', 'geometry')
            ->orderBy('id')
            ->get();
        $allowedLanduseIds = $landuses->pluck('id');

        $mapData = [
            'buildings' => $buildings->map(fn (Building $building) => [
                'id' => $building->id,
                'name' => $building->name,
                'geometry' => $building->geometry,
                'color' => $building->color,
            ])->values(),
            'landuses' => $landuses->map(fn (Landuse $landuse) => [
                'id' => $landuse->id,
                'name' => $landuse->name,
                'geometry' => $landuse->geometry,
                'type' => data_get($landuse->properties, 'type'),
            ])->values(),
            'paths' => $paths->map(fn (Path $path) => [
                'id' => $path->id,
                'name' => $path->name,
                'type' => $path->type,
                'geometry' => $path->geometry,
            ])->values(),
            'rooms' => $rooms->map(fn (IndoorRoom $room) => [
                'id' => $room->id,
                'building_id' => $room->indoorMap?->building_id,
                'building_name' => $room->indoorMap?->building?->name,
                'floor_label' => $room->indoorMap?->floor_label,
                'room_code' => $room->room_code,
                'name' => $room->name,
                'type' => $room->type,
            ])->values(),
        ];

        $requestedGroup = trim((string) $request->query('destination_group', 'all'));
        $selectedBuildingId = null;
        $selectedGroup = 'all';

        if ($requestedGroup === 'landuse') {
            $selectedGroup = 'landuse';
        } elseif (preg_match('/^building:(\d+)$/', $requestedGroup, $matches)) {
            $candidateId = (int) $matches[1];

            if ($buildings->contains('id', $candidateId)) {
                $selectedBuildingId = $candidateId;
                $selectedGroup = "building:{$candidateId}";
            }
        }

        $keywordsQuery = DestinationKeyword::query()
            ->where(function ($visibleQuery) use ($allowedLanduseIds) {
                $visibleQuery->where('destination_type', '!=', 'landuse')
                    ->orWhereIn('destination_id', $allowedLanduseIds);
            })
            ->when($selectedBuildingId !== null, function ($query) use ($selectedBuildingId, $rooms) {
                $roomIds = $rooms
                    ->filter(fn (IndoorRoom $room) => $room->indoorMap?->building_id === $selectedBuildingId)
                    ->pluck('id');

                $query->where(function ($groupQuery) use ($selectedBuildingId, $roomIds) {
                    $groupQuery->where(function ($buildingQuery) use ($selectedBuildingId) {
                        $buildingQuery->where('destination_type', 'building')
                            ->where('destination_id', $selectedBuildingId);
                    })->orWhere(function ($roomQuery) use ($roomIds) {
                        $roomQuery->where('destination_type', 'room')
                            ->whereIn('destination_id', $roomIds);
                    });
                });
            })
            ->when($selectedGroup === 'landuse', fn ($query) => $query->where('destination_type', 'landuse'));

        $keywords = $keywordsQuery
            ->when($search !== '', function ($query) use ($search, $pattern) {
                $query->where(function ($searchQuery) use ($search, $pattern) {
                    $searchQuery->where('keyword', 'LIKE', $pattern)
                        ->orWhere('destination_type', 'LIKE', $pattern)
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'building')
                                ->whereIn('destination_id', Building::query()
                                    ->select('id')
                                    ->where('name', 'LIKE', $pattern));
                        })
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'landuse')
                                ->whereIn('destination_id', Landuse::query()
                                    ->select('id')
                                    ->where('name', 'LIKE', $pattern));
                        })
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'room')
                                ->whereIn('destination_id', IndoorRoom::query()
                                    ->select('id')
                                    ->where(function ($roomQuery) use ($pattern) {
                                        $roomQuery->where('name', 'LIKE', $pattern)
                                            ->orWhere('room_code', 'LIKE', $pattern)
                                            ->orWhere('type', 'LIKE', $pattern)
                                            ->orWhereHas('indoorMap', function ($mapQuery) use ($pattern) {
                                                $mapQuery->where('name', 'LIKE', $pattern)
                                                    ->orWhere('floor_label', 'LIKE', $pattern)
                                                    ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                                                        $buildingQuery->where('name', 'LIKE', $pattern);
                                                    });
                                            });
                                    }));
                        });

                    if (is_numeric($search)) {
                        $numericSearch = (int) $search;
                        $searchQuery->orWhere('id', $numericSearch)
                            ->orWhere('destination_id', $numericSearch)
                            ->orWhere('priority', $numericSearch);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $buildingLookup = $buildings->keyBy('id');
        $landuseLookup = $landuses->keyBy('id');
        $roomLookup = $rooms->keyBy('id');

        $keywords->getCollection()->transform(function (DestinationKeyword $keyword) use (
            $buildingLookup,
            $landuseLookup,
            $roomLookup
        ) {
            if ($keyword->destination_type === 'building') {
                $building = $buildingLookup->get($keyword->destination_id);
                $keyword->destination_label = $building?->name ?? "Building #{$keyword->destination_id}";
                $keyword->destination_context = 'Entire building';
            } elseif ($keyword->destination_type === 'landuse') {
                $landuse = $landuseLookup->get($keyword->destination_id);
                $keyword->destination_label = $landuse?->name ?? "Land-use #{$keyword->destination_id}";
                $keyword->destination_context = 'Outdoor area';
            } else {
                $room = $roomLookup->get($keyword->destination_id);
                $keyword->destination_label = $room
                    ? ($room->room_code ?: ($room->name ?: "Room #{$keyword->destination_id}"))
                    : "Room #{$keyword->destination_id}";
                $keyword->destination_context = collect([
                    $room?->name,
                    $room?->indoorMap?->building?->name,
                    $room?->indoorMap?->floor_label,
                ])->filter()->implode(' · ') ?: 'Indoor room';
            }

            return $keyword;
        });

        $roomBuildingIds = $rooms->mapWithKeys(
            fn (IndoorRoom $room) => [$room->id => $room->indoorMap?->building_id]
        );
        $buildingKeywordCounts = $buildings->mapWithKeys(fn (Building $building) => [$building->id => 0]);
        $landuseKeywordCount = 0;

        $visibleKeywordTargets = DestinationKeyword::query()
            ->where(function ($visibleQuery) use ($allowedLanduseIds) {
                $visibleQuery->where('destination_type', '!=', 'landuse')
                    ->orWhereIn('destination_id', $allowedLanduseIds);
            })
            ->get(['destination_type', 'destination_id'])
            ->each(function (DestinationKeyword $keyword) use (
                &$buildingKeywordCounts,
                &$landuseKeywordCount,
                $roomBuildingIds
            ) {
                if ($keyword->destination_type === 'building' && $buildingKeywordCounts->has($keyword->destination_id)) {
                    $buildingKeywordCounts->put(
                        $keyword->destination_id,
                        $buildingKeywordCounts->get($keyword->destination_id, 0) + 1
                    );
                } elseif ($keyword->destination_type === 'room') {
                    $buildingId = $roomBuildingIds->get($keyword->destination_id);

                    if ($buildingId && $buildingKeywordCounts->has($buildingId)) {
                        $buildingKeywordCounts->put(
                            $buildingId,
                            $buildingKeywordCounts->get($buildingId, 0) + 1
                        );
                    }
                } elseif ($keyword->destination_type === 'landuse') {
                    $landuseKeywordCount++;
                }
            });
        $allKeywordCount = $visibleKeywordTargets->count();

        return view('admin.Destination.Destination_keyword', compact(
            'buildings',
            'landuses',
            'rooms',
            'mapData',
            'keywords',
            'search',
            'selectedGroup',
            'buildingKeywordCounts',
            'landuseKeywordCount',
            'allKeywordCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination_type' => 'required|in:building,landuse,room',
            'destination_id' => 'required|integer',
            'keywords' => 'required|string',
            'priority' => 'nullable|integer|in:1,2,3',
        ]);

        $destinationType = $request->destination_type;
        $destinationId = (int) $request->destination_id;
        $priority = (int) ($request->priority ?? 1);

        if (! $this->destinationExists($destinationType, $destinationId)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selected destination does not exist.');
        }

        $rawKeywords = explode(',', $request->keywords);
        $savedCount = 0;

        foreach ($rawKeywords as $keyword) {
            $cleanKeyword = trim($keyword);

            if ($cleanKeyword === '') {
                continue;
            }

            $alreadyExists = DestinationKeyword::whereRaw('LOWER(keyword) = ?', [mb_strtolower($cleanKeyword)])
                ->where('destination_type', $destinationType)
                ->where('destination_id', $destinationId)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DestinationKeyword::create([
                'keyword' => $cleanKeyword,
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'priority' => $priority,
                'is_active' => true,
            ]);

            $savedCount++;
        }

        if ($savedCount === 0) {
            return redirect()
                ->back()
                ->with('error', 'No new keyword was saved. Possible duplicate or empty input.');
        }

        return redirect()
            ->route('admin.destination-keyword')
            ->with('success', $savedCount.' keyword(s) saved successfully.');
    }

    public function destroy(DestinationKeyword $destinationKeyword)
    {
        $destinationKeyword->delete();

        return redirect()
            ->route('admin.destination-keyword')
            ->with('success', 'Keyword deleted successfully.');
    }

    public function sync(DestinationKeywordSynchronizer $synchronizer)
    {
        $result = $synchronizer->sync();

        return redirect()
            ->route('admin.destination-keyword')
            ->with(
                'success',
                "{$result['created']} missing keyword(s) generated for "
                ."{$result['buildings']} buildings and {$result['rooms']} rooms."
            );
    }

    private function destinationExists(string $type, int $id): bool
    {
        return match ($type) {
            'building' => Building::where('id', $id)->exists(),
            'landuse' => ($landuse = Landuse::find($id)) && ! $this->isDesignLanduse($landuse),
            'room' => IndoorRoom::where('id', $id)->exists(),
            default => false,
        };
    }

    private function isDesignLanduse(Landuse $landuse): bool
    {
        return strtolower(trim((string) data_get($landuse->properties, 'type'))) === 'design';
    }
}
