<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\BuildingEntrance;
use App\Models\BuildingEntranceLink;
use App\Models\IndoorEntrance;
use Illuminate\Http\Request;

class BuildingEntranceLinkController extends Controller
{
    public function BuildingEntranceLink(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $buildings = Building::query()
            ->select('id', 'name', 'geometry', 'color')
            ->orderBy('name')
            ->get();

        $buildingMapData = $buildings
            ->filter(fn (Building $building): bool => is_array($building->geometry))
            ->map(fn (Building $building): array => [
                'id' => $building->id,
                'name' => $building->name,
                'geometry' => $building->geometry,
                'color' => $building->color ?: '#68a7ee',
            ])
            ->values();

        $buildingEntrances = BuildingEntrance::with('building')
            ->orderBy('building_id')
            ->orderBy('name')
            ->get();

        $indoorEntrances = IndoorEntrance::with('indoorMap.building')
            ->whereIn('ent_type', ['main', 'side', 'back'])
            ->orderBy('name')
            ->get();

        $links = BuildingEntranceLink::with([
            'building',
            'buildingEntrance.building',
            'indoorEntrance.indoorMap.building',
        ])
            ->when($search !== '', function ($query) use ($search, $pattern) {
                $query->where(function ($searchQuery) use ($search, $pattern) {
                    $searchQuery->where('name', 'LIKE', $pattern)
                        ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                            $buildingQuery->where('name', 'LIKE', $pattern);
                        })
                        ->orWhereHas('buildingEntrance', function ($entranceQuery) use ($pattern) {
                            $entranceQuery->where(function ($entranceSearchQuery) use ($pattern) {
                                $entranceSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('latitude', 'LIKE', $pattern)
                                    ->orWhere('longitude', 'LIKE', $pattern)
                                    ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                                        $buildingQuery->where('name', 'LIKE', $pattern);
                                    });
                            });
                        })
                        ->orWhereHas('indoorEntrance', function ($entranceQuery) use ($search, $pattern) {
                            $entranceQuery->where(function ($entranceSearchQuery) use ($search, $pattern) {
                                $entranceSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('room_code', 'LIKE', $pattern)
                                    ->orWhere('ent_type', 'LIKE', $pattern)
                                    ->orWhereHas('indoorMap', function ($mapQuery) use ($search, $pattern) {
                                        $mapQuery->where(function ($mapSearchQuery) use ($search, $pattern) {
                                            $mapSearchQuery->where('name', 'LIKE', $pattern)
                                                ->orWhere('floor_label', 'LIKE', $pattern)
                                                ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                                                    $buildingQuery->where('name', 'LIKE', $pattern);
                                                });

                                            if (is_numeric($search)) {
                                                $mapSearchQuery->orWhere('floor_number', (int) $search);
                                            }
                                        });
                                    });
                            });
                        });

                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.building_entrance_link.building_entrance_link', compact(
            'buildings',
            'buildingMapData',
            'buildingEntrances',
            'indoorEntrances',
            'links',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'building_entrance_id' => 'required|exists:building_entrances,id',
            'indoor_entrance_id' => 'required|exists:indoor_entrances,id',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $buildingEntrance = BuildingEntrance::findOrFail($request->building_entrance_id);
            $indoorEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->indoor_entrance_id);

            if (! in_array($indoorEntrance->ent_type, ['main', 'side', 'back'])) {
                return back()->with('error', 'Selected indoor entrance must be main, side, or back entrance.');
            }

            if (! $indoorEntrance->indoorMap) {
                return back()->with('error', 'Selected indoor entrance does not belong to a valid indoor map.');
            }

            if ((int) $buildingEntrance->building_id !== (int) $request->building_id) {
                return back()->with('error', 'Selected outdoor entrance does not belong to the selected building.');
            }

            if ((int) $indoorEntrance->indoorMap->building_id !== (int) $request->building_id) {
                return back()->with('error', 'Selected indoor entrance does not belong to the selected building.');
            }

            $existing = BuildingEntranceLink::where('building_id', $request->building_id)
                ->where('building_entrance_id', $request->building_entrance_id)
                ->where('indoor_entrance_id', $request->indoor_entrance_id)
                ->first();

            if ($existing) {
                return back()->with('error', 'This building entrance link already exists.');
            }

            BuildingEntranceLink::create([
                'building_id' => $request->building_id,
                'building_entrance_id' => $request->building_entrance_id,
                'indoor_entrance_id' => $request->indoor_entrance_id,
                'name' => $request->name,
            ]);

            return back()->with('success', 'Building entrance link created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Create failed: '.$e->getMessage());
        }
    }

    public function update(Request $request, BuildingEntranceLink $link)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'building_entrance_id' => 'required|exists:building_entrances,id',
            'indoor_entrance_id' => 'required|exists:indoor_entrances,id',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $buildingEntrance = BuildingEntrance::findOrFail($request->building_entrance_id);
            $indoorEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->indoor_entrance_id);

            if (! in_array($indoorEntrance->ent_type, ['main', 'side', 'back'])) {
                return back()->with('error', 'Selected indoor entrance must be main, side, or back entrance.');
            }

            if (! $indoorEntrance->indoorMap) {
                return back()->with('error', 'Selected indoor entrance does not belong to a valid indoor map.');
            }

            if ((int) $buildingEntrance->building_id !== (int) $request->building_id) {
                return back()->with('error', 'Selected outdoor entrance does not belong to the selected building.');
            }

            if ((int) $indoorEntrance->indoorMap->building_id !== (int) $request->building_id) {
                return back()->with('error', 'Selected indoor entrance does not belong to the selected building.');
            }

            $existing = BuildingEntranceLink::where('id', '!=', $link->id)
                ->where('building_id', $request->building_id)
                ->where('building_entrance_id', $request->building_entrance_id)
                ->where('indoor_entrance_id', $request->indoor_entrance_id)
                ->first();

            if ($existing) {
                return back()->with('error', 'This building entrance link already exists.');
            }

            $link->update([
                'building_id' => $request->building_id,
                'building_entrance_id' => $request->building_entrance_id,
                'indoor_entrance_id' => $request->indoor_entrance_id,
                'name' => $request->name,
            ]);

            return back()->with('success', 'Building entrance link updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    public function destroy(BuildingEntranceLink $link)
    {
        try {
            $link->delete();

            return back()->with('success', 'Building entrance link deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }
}
