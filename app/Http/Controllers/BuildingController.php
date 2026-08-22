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
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $buildings = Building::query()
            ->when($search !== '', function ($query) use ($search, $pattern) {
                $query->where(function ($q) use ($search, $pattern) {
                    $q->where('name', 'LIKE', $pattern)
                        ->orWhere('color', 'LIKE', $pattern);

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

    public function labelEditor()
    {
        $buildings = Building::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'geometry',
                'color',
                'show_map_label',
                'map_label_text',
                'map_label_scale',
                'map_label_offset_x',
                'map_label_offset_y',
                'map_label_min_zoom',
            ]);

        return view('admin.buildings.label-editor', compact('buildings'));
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

            if (! $this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format. FeatureCollection or features not found.');
            }

            $folderPath = public_path('Buildings');
            $currentFilePath = $folderPath.DIRECTORY_SEPARATOR.'buildings.geojson';
            $backupFilePath = $folderPath.DIRECTORY_SEPARATOR.'buildings_backup.geojson';

            if (! File::exists($folderPath)) {
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

            if (! $this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson) {
                $this->replaceBuildingsFromGeoJson($savedGeojson);
            });

            return back()->with('success', 'Buildings uploaded successfully. Current file saved and previous version backed up.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }

    public function resetBuildings()
    {
        try {
            $folderPath = public_path('Buildings');
            $currentFilePath = $folderPath.DIRECTORY_SEPARATOR.'buildings.geojson';
            $backupFilePath = $folderPath.DIRECTORY_SEPARATOR.'buildings_backup.geojson';

            if (! File::exists($backupFilePath)) {
                return back()->with('error', 'No backup file found. Nothing to restore.');
            }

            $backupContent = File::get($backupFilePath);
            $backupGeojson = json_decode($backupContent, true);

            if (! $this->isValidGeoJson($backupGeojson)) {
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
            return back()->with('error', 'Reset failed: '.$e->getMessage());
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

    public function updateMapLabel(Request $request, Building $building)
    {
        $validated = $request->validate([
            'show_map_label' => ['required', 'boolean'],
        ]);

        $building->show_map_label = $validated['show_map_label'];
        $building->save();

        return response()->json([
            'success' => true,
            'message' => $building->show_map_label
                ? 'Permanent map label enabled.'
                : 'Permanent map label hidden.',
            'show_map_label' => $building->show_map_label,
        ]);
    }

    public function updateLabelLayout(Request $request, Building $building)
    {
        $validated = $request->validate([
            'show_map_label' => ['required', 'boolean'],
            'map_label_text' => ['nullable', 'string', 'max:80'],
            'map_label_scale' => ['required', 'numeric', 'min:0.65', 'max:1.6'],
            'map_label_offset_x' => ['required', 'integer', 'min:-120', 'max:120'],
            'map_label_offset_y' => ['required', 'integer', 'min:-120', 'max:120'],
            'map_label_min_zoom' => ['required', 'integer', 'between:17,19'],
        ]);

        $labelText = trim((string) ($validated['map_label_text'] ?? ''));

        $building->fill([
            'show_map_label' => $validated['show_map_label'],
            'map_label_text' => $labelText !== '' ? $labelText : null,
            'map_label_scale' => round((float) $validated['map_label_scale'], 2),
            'map_label_offset_x' => (int) $validated['map_label_offset_x'],
            'map_label_offset_y' => (int) $validated['map_label_offset_y'],
            'map_label_min_zoom' => (int) $validated['map_label_min_zoom'],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Building label layout saved.',
            'label' => [
                'show_map_label' => $building->show_map_label,
                'map_label_text' => $building->map_label_text,
                'map_label_scale' => $building->map_label_scale,
                'map_label_offset_x' => $building->map_label_offset_x,
                'map_label_offset_y' => $building->map_label_offset_y,
                'map_label_min_zoom' => $building->map_label_min_zoom,
            ],
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
        $savedLabelSettings = Building::query()
            ->get([
                'name',
                'show_map_label',
                'map_label_text',
                'map_label_scale',
                'map_label_offset_x',
                'map_label_offset_y',
                'map_label_min_zoom',
            ])
            ->mapWithKeys(fn (Building $building) => [
                mb_strtolower(trim((string) $building->name)) => [
                    'show_map_label' => (bool) $building->show_map_label,
                    'map_label_text' => $building->map_label_text,
                    'map_label_scale' => (float) $building->map_label_scale,
                    'map_label_offset_x' => (int) $building->map_label_offset_x,
                    'map_label_offset_y' => (int) $building->map_label_offset_y,
                    'map_label_min_zoom' => (int) $building->map_label_min_zoom,
                ],
            ]);

        Building::query()->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                ! isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                ! isset($feature['geometry'])
            ) {
                continue;
            }

            $featureProperties = $feature['properties'] ?? [];
            $featureColor = $featureProperties['color'] ?? '#2b82cc';
            $buildingName = $featureProperties['name'] ?? 'No Name';
            $normalizedBuildingName = mb_strtolower(trim((string) $buildingName));
            $savedSettings = $savedLabelSettings->get($normalizedBuildingName, []);
            $showMapLabel = array_key_exists('show_map_label', $featureProperties)
                ? filter_var($featureProperties['show_map_label'], FILTER_VALIDATE_BOOL)
                : (bool) ($savedSettings['show_map_label'] ?? false);
            $mapLabelText = trim((string) (
                $featureProperties['map_label_text']
                ?? ($savedSettings['map_label_text'] ?? '')
            ));
            $mapLabelScale = max(0.65, min(1.6, (float) (
                $featureProperties['map_label_scale']
                ?? ($savedSettings['map_label_scale'] ?? 1)
            )));
            $mapLabelOffsetX = max(-120, min(120, (int) (
                $featureProperties['map_label_offset_x']
                ?? ($savedSettings['map_label_offset_x'] ?? 0)
            )));
            $mapLabelOffsetY = max(-120, min(120, (int) (
                $featureProperties['map_label_offset_y']
                ?? ($savedSettings['map_label_offset_y'] ?? 0)
            )));
            $mapLabelMinZoom = max(17, min(19, (int) (
                $featureProperties['map_label_min_zoom']
                ?? ($savedSettings['map_label_min_zoom'] ?? 18)
            )));

            Building::create([
                'name' => $buildingName,
                'geometry' => $feature['geometry'],
                'properties' => $featureProperties,
                'color' => $featureColor,
                'show_map_label' => $showMapLabel,
                'map_label_text' => $mapLabelText !== '' ? mb_substr($mapLabelText, 0, 80) : null,
                'map_label_scale' => round($mapLabelScale, 2),
                'map_label_offset_x' => $mapLabelOffsetX,
                'map_label_offset_y' => $mapLabelOffsetY,
                'map_label_min_zoom' => $mapLabelMinZoom,
            ]);
        }
    }
}
