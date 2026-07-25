<?php

namespace App\Http\Controllers;

use App\Models\IndoorMap;
use App\Models\IndoorPath;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndoorPathController extends Controller
{
    public function IndoorPath()
    {
        $indoorMaps = IndoorMap::with('building')
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->get();

        $paths = IndoorPath::with('indoorMap.building')
            ->latest()
            ->paginate(10);

        return view('admin.indoor_path.indoor_path', compact('indoorMaps', 'paths'));
    }

    public function uploadIndoorPaths(Request $request)
    {
        $request->validate([
            'indoor_map_id' => 'required|exists:indoor_maps,id',
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['LineString', 'MultiLineString'])],
        ]);

        try {
            $indoorMap = IndoorMap::with('building')->findOrFail($request->indoor_map_id);

            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (!$this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format. FeatureCollection or features not found.');
            }

            $buildingName = $indoorMap->building->name ?? 'building';
            $buildingSlug = str($buildingName)->slug('_');
            $floorLabel = strtolower($indoorMap->floor_label ?? ($indoorMap->floor_number . 'f'));

            $folderPath = public_path("indoor_paths/{$buildingSlug}/{$floorLabel}");
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_paths.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_paths_backup.geojson';

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            if (File::exists($currentFilePath)) {
                File::copy($currentFilePath, $backupFilePath);
            }

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            $file->move($folderPath, 'indoor_paths.geojson');

            $savedContent = File::get($currentFilePath);
            $savedGeojson = json_decode($savedContent, true);

            if (!$this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson, $indoorMap) {
                $this->replaceIndoorPathsFromGeoJson($savedGeojson, $indoorMap->id);
            });

            return back()->with('success', 'Indoor paths uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function updateIndoorPath(Request $request, IndoorPath $path)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'path_type' => 'nullable|string|max:255',
            'is_blocked' => 'required|in:0,1',
        ]);

        try {
            $path->name = $request->name;
            $path->path_type = $request->path_type;
            $path->is_blocked = (bool) $request->is_blocked;

            $properties = $path->properties ?? [];
            $properties['name'] = $request->name;
            $properties['path_type'] = $request->path_type;
            $properties['is_blocked'] = (bool) $request->is_blocked;

            $path->properties = $properties;
            $path->save();

            return back()->with('success', 'Indoor path updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
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

            $deletedCount = IndoorPath::whereIn('indoor_map_id', $indoorMapIds)->count();

            IndoorPath::whereIn('indoor_map_id', $indoorMapIds)->delete();

            return back()->with('success', $deletedCount . ' indoor path(s) deleted successfully for the selected building.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete all failed: ' . $e->getMessage());
        }
    }

    public function deleteIndoorPath(IndoorPath $path)
    {
        try {
            $path->delete();

            return back()->with('success', 'Indoor path deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
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

    private function replaceIndoorPathsFromGeoJson(array $geojson, int $indoorMapId): void
    {
        IndoorPath::where('indoor_map_id', $indoorMapId)->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                !isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                !isset($feature['geometry'])
            ) {
                continue;
            }

            $properties = $feature['properties'] ?? [];

            IndoorPath::create([
                'indoor_map_id' => $indoorMapId,
                'name' => $properties['name'] ?? 'No Name',
                'path_type' => $properties['path_type'] ?? 'hallway',
                'geometry' => $feature['geometry'],
                'is_blocked' => (bool) ($properties['is_blocked'] ?? false),
                'properties' => $properties,
            ]);
        }
    }
}
