<?php

namespace App\Http\Controllers;

use App\Models\Path;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PathController extends Controller
{
    public function Path()
    {
        $paths = Path::latest()->paginate(10);
        return view('admin.path.path', compact('paths'));
    }

    public function uploadPath(Request $request)
    {
        $request->validate([
            'geojson' => 'required|file|mimes:json,geojson,txt',
        ]);

        try {
            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (!$this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format. FeatureCollection or features not found.');
            }

            $folderPath = public_path('Paths');
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'path.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'path_backup.geojson';

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            if (File::exists($currentFilePath)) {
                File::copy($currentFilePath, $backupFilePath);
                File::delete($currentFilePath);
            }

            $file->move($folderPath, 'path.geojson');

            $savedContent = File::get($currentFilePath);
            $savedGeojson = json_decode($savedContent, true);

            if (!$this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson) {
                $this->replacePathsFromGeoJson($savedGeojson);
            });

            return back()->with('success', 'Paths uploaded successfully. Current file saved and previous version backed up.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function resetPath()
    {
        try {
            $folderPath = public_path('Paths');
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'path.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'path_backup.geojson';

            if (!File::exists($backupFilePath)) {
                return back()->with('error', 'No backup file found. Nothing to restore.');
            }

            $backupContent = File::get($backupFilePath);
            $backupGeojson = json_decode($backupContent, true);

            if (!$this->isValidGeoJson($backupGeojson)) {
                return back()->with('error', 'Backup GeoJSON is invalid.');
            }

            DB::transaction(function () use ($backupGeojson) {
                $this->replacePathsFromGeoJson($backupGeojson);
            });

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            File::copy($backupFilePath, $currentFilePath);

            return back()->with('success', 'Previous path upload restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }
    }

    public function updateName(Request $request, Path $path)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newName = trim($request->name);

        if ($newName === '') {
            return response()->json([
                'success' => false,
                'message' => 'Path name is required.',
            ], 422);
        }

        $path->name = $newName;

        $properties = $path->properties ?? [];
        $properties['name'] = $newName;
        $path->properties = $properties;

        $path->save();

        return response()->json([
            'success' => true,
            'message' => 'Path name updated successfully.',
            'name' => $path->name,
        ]);
    }

    public function updateSettings(Request $request, Path $path)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:walkway,stairs,covered_stairs,road',
            'risk_level' => 'required|integer|min:1|max:3',
            'difficulty_level' => 'required|integer|min:1|max:3',
            'is_blocked' => 'required|boolean',
            'hazard_note' => 'nullable|string|max:1000',
        ]);

        $path->type = $validated['type'];
        $path->risk_level = $validated['risk_level'];
        $path->difficulty_level = $validated['difficulty_level'];
        $path->is_blocked = (bool) $validated['is_blocked'];
        $path->hazard_note = $validated['hazard_note'] ?? null;

        $properties = $path->properties ?? [];
        $properties['type'] = $path->type;
        $properties['risk_level'] = $path->risk_level;
        $properties['difficulty_level'] = $path->difficulty_level;
        $properties['is_blocked'] = $path->is_blocked;
        $properties['hazard_note'] = $path->hazard_note;
        $path->properties = $properties;

        $path->save();

        return response()->json([
            'success' => true,
            'message' => 'Path settings updated successfully.',
            'type' => $path->type,
            'risk_level' => $path->risk_level,
            'difficulty_level' => $path->difficulty_level,
            'is_blocked' => $path->is_blocked,
            'hazard_note' => $path->hazard_note,
            'blocked_label' => $path->is_blocked ? 'Blocked' : 'Open',
            'blocked_badge_class' => $path->is_blocked ? 'bg-danger' : 'bg-success',
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

    private function replacePathsFromGeoJson(array $geojson): void
    {
        Path::query()->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                !isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                !isset($feature['geometry'])
            ) {
                continue;
            }

            $properties = $feature['properties'] ?? [];

            Path::create([
                'name' => $properties['name'] ?? 'No Name',
                'geometry' => $feature['geometry'],
                'type' => $properties['type'] ?? 'walkway',
                'risk_level' => isset($properties['risk_level']) ? (int) $properties['risk_level'] : 1,
                'difficulty_level' => isset($properties['difficulty_level']) ? (int) $properties['difficulty_level'] : 1,
                'is_blocked' => isset($properties['is_blocked']) ? (bool) $properties['is_blocked'] : false,
                'hazard_note' => $properties['hazard_note'] ?? null,
                'properties' => $properties,
            ]);
        }
    }
}
