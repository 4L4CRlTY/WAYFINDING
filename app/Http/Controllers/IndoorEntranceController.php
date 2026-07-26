<?php

namespace App\Http\Controllers;

use App\Models\IndoorEntrance;
use App\Models\IndoorMap;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndoorEntranceController extends Controller
{
    public function IndoorEntrances(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $indoorMaps = IndoorMap::with('building')
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->get();

        $entrances = IndoorEntrance::with('indoorMap.building')
            ->when($search !== '', function ($query) use ($search, $pattern) {
                $query->where(function ($searchQuery) use ($search, $pattern) {
                    $searchQuery->where('name', 'LIKE', $pattern)
                        ->orWhere('ent_type', 'LIKE', $pattern)
                        ->orWhere('room_code', 'LIKE', $pattern)
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

                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.indoor_entrances.indoor_entrance', compact('indoorMaps', 'entrances', 'search'));
    }

    public function uploadIndoorEntrances(Request $request)
    {
        $request->validate([
            'indoor_map_id' => 'required|exists:indoor_maps,id',
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['Point'])],
        ]);

        try {
            $indoorMap = IndoorMap::with('building')->findOrFail($request->indoor_map_id);

            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (! $this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format. FeatureCollection or features not found.');
            }

            $buildingName = $indoorMap->building->name ?? 'building';
            $buildingSlug = str($buildingName)->slug('_');
            $floorLabel = strtolower($indoorMap->floor_label ?? ($indoorMap->floor_number.'f'));

            $folderPath = public_path("indoor_entrances/{$buildingSlug}/{$floorLabel}");
            $currentFilePath = $folderPath.DIRECTORY_SEPARATOR.'indoor_entrances.geojson';
            $backupFilePath = $folderPath.DIRECTORY_SEPARATOR.'indoor_entrances_backup.geojson';

            if (! File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            if (File::exists($currentFilePath)) {
                File::copy($currentFilePath, $backupFilePath);
            }

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            $file->move($folderPath, 'indoor_entrances.geojson');

            $savedContent = File::get($currentFilePath);
            $savedGeojson = json_decode($savedContent, true);

            if (! $this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson, $indoorMap) {
                $this->replaceIndoorEntrancesFromGeoJson($savedGeojson, $indoorMap->id);
            });

            return back()->with('success', 'Indoor entrances uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }

    public function updateIndoorEntrance(Request $request, IndoorEntrance $entrance)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ent_type' => 'nullable|string|max:255',
            'room_code' => 'nullable|string|max:255',
        ]);

        try {
            $entrance->name = $request->name;
            $entrance->ent_type = $request->ent_type;
            $entrance->room_code = $request->room_code;

            $properties = $entrance->properties ?? [];
            $properties['name'] = $request->name;
            $properties['ent_type'] = $request->ent_type;
            $properties['room_code'] = $request->room_code;

            $entrance->properties = $properties;
            $entrance->save();

            return back()->with('success', 'Indoor entrance updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    public function deleteByBuilding(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
        ]);

        try {
            $indoorMapIds = IndoorMap::where('building_id', $request->building_id)->pluck('id');

            if ($indoorMapIds->isEmpty()) {
                return back()->with('error', 'No indoor maps found for the selected building.');
            }

            $deletedCount = IndoorEntrance::whereIn('indoor_map_id', $indoorMapIds)->count();

            IndoorEntrance::whereIn('indoor_map_id', $indoorMapIds)->delete();

            return back()->with('success', $deletedCount.' indoor entrance(s) deleted successfully for the selected building.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete all failed: '.$e->getMessage());
        }
    }

    public function deleteIndoorEntrance(IndoorEntrance $entrance)
    {
        try {
            $entrance->delete();

            return back()->with('success', 'Indoor entrance deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }

    private function isValidGeoJson($geojson): bool
    {
        return is_array($geojson)
            && isset($geojson['type'])
            && $geojson['type'] === 'FeatureCollection'
            && isset($geojson['features'])
            && is_array($geojson['features']);
    }

    private function replaceIndoorEntrancesFromGeoJson(array $geojson, int $indoorMapId): void
    {
        IndoorEntrance::where('indoor_map_id', $indoorMapId)->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                ! isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                ! isset($feature['geometry'])
            ) {
                continue;
            }

            $properties = $feature['properties'] ?? [];

            IndoorEntrance::create([
                'indoor_map_id' => $indoorMapId,
                'name' => $properties['name'] ?? 'No Name',
                'ent_type' => $properties['ent_type'] ?? null,
                'room_code' => $properties['room_code'] ?? null,
                'geometry' => $feature['geometry'],
                'properties' => $properties,
            ]);
        }
    }
}
