<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BuildingController extends Controller
{
    public function Buildings(Request $request)
    {
        $search = trim($request->get('search', ''));

        $buildings = Building::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('color', 'LIKE', "%{$search}%");

                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.buildings.building', compact('buildings', 'search'));
    }

    public function uploadBuildings(Request $request)
    {
        $request->validate([
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['Polygon', 'MultiPolygon'])],
        ]);

        try {
            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (!$this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format. FeatureCollection or features not found.');
            }

            $folderPath = public_path('Buildings');
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'buildings.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'buildings_backup.geojson';

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            if (File::exists($currentFilePath)) {
                File::copy($currentFilePath, $backupFilePath);
            }

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            $file->move($folderPath, 'buildings.geojson');

            $savedContent = File::get($currentFilePath);
            $savedGeojson = json_decode($savedContent, true);

            if (!$this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson) {
                $this->replaceBuildingsFromGeoJson($savedGeojson);
            });

            return back()->with('success', 'Buildings uploaded successfully. Current file saved and previous version backed up.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function resetBuildings()
    {
        try {
            $folderPath = public_path('Buildings');
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'buildings.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'buildings_backup.geojson';

            if (!File::exists($backupFilePath)) {
                return back()->with('error', 'No backup file found. Nothing to restore.');
            }

            $backupContent = File::get($backupFilePath);
            $backupGeojson = json_decode($backupContent, true);

            if (!$this->isValidGeoJson($backupGeojson)) {
                return back()->with('error', 'Backup GeoJSON is invalid.');
            }

            DB::transaction(function () use ($backupGeojson) {
                $this->replaceBuildingsFromGeoJson($backupGeojson);
            });

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            File::copy($backupFilePath, $currentFilePath);

            return back()->with('success', 'Previous buildings upload restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }
    }

    public function updateName(Request $request, Building $building)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newName = trim($request->name);

        if ($newName === '') {
            return response()->json([
                'success' => false,
                'message' => 'Building name is required.',
            ], 422);
        }

        $building->name = $newName;

        $properties = $building->properties ?? [];
        $properties['name'] = $newName;
        $building->properties = $properties;

        $building->save();

        return response()->json([
            'success' => true,
            'message' => 'Building name updated successfully.',
            'name' => $building->name,
        ]);
    }

    public function updateColor(Request $request, Building $building)
    {
        $request->validate([
            'color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        $building->color = $request->color;

        $properties = $building->properties ?? [];
        $properties['color'] = $request->color;
        $building->properties = $properties;

        $building->save();

        return response()->json([
            'success' => true,
            'message' => 'Building color updated successfully.',
            'color' => $building->color,
        ]);
    }

    private function isValidGeoJson($geojson): bool
    {
        return is_array($geojson)
            && isset($geojson['type'])
            && $geojson['type'] === 'FeatureCollection'
            && isset($geojson['features'])
            && is_array($geojson['features']);
    }

    private function replaceBuildingsFromGeoJson(array $geojson): void
    {
        Building::query()->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                !isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                !isset($feature['geometry'])
            ) {
                continue;
            }

            $featureProperties = $feature['properties'] ?? [];
            $featureColor = $featureProperties['color'] ?? '#2b82cc';

            Building::create([
                'name' => $featureProperties['name'] ?? 'No Name',
                'geometry' => $feature['geometry'],
                'properties' => $featureProperties,
                'color' => $featureColor,
            ]);
        }
    }
}
