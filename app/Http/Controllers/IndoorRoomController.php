<?php

namespace App\Http\Controllers;

use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndoorRoomController extends Controller
{
    public function IndoorRoom()
    {
        $indoorMaps = IndoorMap::with('building')
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->get();

        $rooms = IndoorRoom::with('indoorMap.building')
            ->latest()
            ->paginate(10);

        return view('admin.indoor_room.indoor_room', compact('indoorMaps', 'rooms'));
    }

    public function uploadIndoorRooms(Request $request)
    {
        $request->validate([
            'indoor_map_id' => 'required|exists:indoor_maps,id',
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['Polygon', 'MultiPolygon'])],
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

            $folderPath = public_path("indoor_rooms/{$buildingSlug}/{$floorLabel}");
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_rooms.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_rooms_backup.geojson';

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            if (File::exists($currentFilePath)) {
                File::copy($currentFilePath, $backupFilePath);
            }

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            $file->move($folderPath, 'indoor_rooms.geojson');

            $savedContent = File::get($currentFilePath);
            $savedGeojson = json_decode($savedContent, true);

            if (!$this->isValidGeoJson($savedGeojson)) {
                return back()->with('error', 'Saved GeoJSON file is invalid.');
            }

            DB::transaction(function () use ($savedGeojson, $indoorMap) {
                $this->replaceIndoorRoomsFromGeoJson($savedGeojson, $indoorMap->id);
            });

            return back()->with('success', 'Indoor rooms uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function resetIndoorRooms(Request $request)
    {
        $request->validate([
            'indoor_map_id' => 'required|exists:indoor_maps,id',
        ]);

        try {
            $indoorMap = IndoorMap::with('building')->findOrFail($request->indoor_map_id);

            $buildingName = $indoorMap->building->name ?? 'building';
            $buildingSlug = str($buildingName)->slug('_');
            $floorLabel = strtolower($indoorMap->floor_label ?? ($indoorMap->floor_number . 'f'));

            $folderPath = public_path("indoor_rooms/{$buildingSlug}/{$floorLabel}");
            $currentFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_rooms.geojson';
            $backupFilePath = $folderPath . DIRECTORY_SEPARATOR . 'indoor_rooms_backup.geojson';

            if (!File::exists($backupFilePath)) {
                return back()->with('error', 'No backup file found. Nothing to restore.');
            }

            $backupContent = File::get($backupFilePath);
            $backupGeojson = json_decode($backupContent, true);

            if (!$this->isValidGeoJson($backupGeojson)) {
                return back()->with('error', 'Backup GeoJSON is invalid.');
            }

            DB::transaction(function () use ($backupGeojson, $indoorMap) {
                $this->replaceIndoorRoomsFromGeoJson($backupGeojson, $indoorMap->id);
            });

            if (File::exists($currentFilePath)) {
                File::delete($currentFilePath);
            }

            File::copy($backupFilePath, $currentFilePath);

            return back()->with('success', 'Previous indoor rooms upload restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }
    }

    public function updateIndoorRoom(Request $request, IndoorRoom $room)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'room_code' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        try {
            $room->name = $request->name;
            $room->room_code = $request->room_code;
            $room->type = $request->type;

            $properties = $room->properties ?? [];
            $properties['name'] = $request->name;
            $properties['room_code'] = $request->room_code;
            $properties['type'] = $request->type;

            $room->properties = $properties;
            $room->save();

            return back()->with('success', 'Indoor room updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
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

    private function replaceIndoorRoomsFromGeoJson(array $geojson, int $indoorMapId): void
    {
        IndoorRoom::where('indoor_map_id', $indoorMapId)->delete();

        foreach ($geojson['features'] as $feature) {
            if (
                !isset($feature['type']) ||
                $feature['type'] !== 'Feature' ||
                !isset($feature['geometry'])
            ) {
                continue;
            }

            $properties = $feature['properties'] ?? [];

            IndoorRoom::create([
                'indoor_map_id' => $indoorMapId,
                'name' => $properties['name'] ?? 'No Name',
                'room_code' => $properties['room_code'] ?? null,
                'type' => $properties['type'] ?? null,
                'geometry' => $feature['geometry'],
                'properties' => $properties,
            ]);
        }
    }
}
