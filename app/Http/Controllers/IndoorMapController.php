<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\IndoorMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class IndoorMapController extends Controller
{
    public function IndoorMap()
    {
        $buildings = Building::orderBy('name')->get();
        $maps = IndoorMap::with('building')->latest()->paginate(10);

        return view('admin.indoor_map.indoor_map', compact('buildings', 'maps'));
    }

    public function uploadIndoorMap(Request $request)
    {
        $request->validate([
            'building_id'     => 'required|exists:buildings,id',
            'floor_number'    => 'required|integer|min:0',
            'floor_label'     => 'nullable|string|max:20',
            'name'            => 'nullable|string|max:255',
            'floorplan_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'geometry_file'   => 'required|file|mimes:json,geojson',
        ]);

        try {
            $building = Building::findOrFail($request->building_id);
            $floorNumber = (int) $request->floor_number;
            $floorLabel = $request->floor_label ?: ($floorNumber === 0 ? 'Basement' : $floorNumber . 'F');
            $name = $request->name ?: ($building->name . ' - ' . $floorLabel);

            $folderPath = public_path('floorplan_image');

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            $geometry = $this->extractGeometryFromUpload($request, 'geometry_file');

            if (!$geometry) {
                return back()->with('error', 'Invalid GeoJSON file. No Polygon or MultiPolygon geometry found.');
            }

            $imageInfo = getimagesize($request->file('floorplan_image')->getRealPath());
            $imageWidth = $imageInfo[0] ?? null;
            $imageHeight = $imageInfo[1] ?? null;

            $buildingSlug = Str::slug($building->name, '_');
            $extension = $request->file('floorplan_image')->getClientOriginalExtension();
            $fileName = $buildingSlug . '_floor_' . $floorNumber . '.' . $extension;

            $existingMap = IndoorMap::where('building_id', $building->id)
                ->where('floor_number', $floorNumber)
                ->first();

            DB::transaction(function () use (
                $request,
                $existingMap,
                $folderPath,
                $fileName,
                $building,
                $floorNumber,
                $floorLabel,
                $name,
                $geometry,
                $imageWidth,
                $imageHeight
            ) {
                if ($existingMap && $existingMap->floorplan_image) {
                    $existingMap->backup_floorplan_image = $existingMap->floorplan_image;
                    $existingMap->save();
                }

                $request->file('floorplan_image')->move($folderPath, $fileName);

                if ($existingMap) {
                    $existingMap->update([
                        'name'            => $name,
                        'floor_number'    => $floorNumber,
                        'floor_label'     => $floorLabel,
                        'floorplan_image' => $fileName,
                        'width'           => $imageWidth,
                        'height'          => $imageHeight,
                        'geometry'        => $geometry,
                        'is_active'       => true,
                    ]);
                } else {
                    IndoorMap::create([
                        'building_id'          => $building->id,
                        'name'                 => $name,
                        'floor_number'         => $floorNumber,
                        'floor_label'          => $floorLabel,
                        'floorplan_image'      => $fileName,
                        'backup_floorplan_image' => null,
                        'width'                => $imageWidth,
                        'height'               => $imageHeight,
                        'geometry'             => $geometry,
                        'is_active'            => true,
                    ]);
                }
            });

            return back()->with('success', 'Indoor map uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function resetIndoorMap(Request $request)
    {
        $request->validate([
            'building_id'  => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0',
        ]);

        try {
            $map = IndoorMap::where('building_id', $request->building_id)
                ->where('floor_number', $request->floor_number)
                ->first();

            if (!$map) {
                return back()->with('error', 'Indoor map not found for the selected building and floor.');
            }

            if (!$map->backup_floorplan_image) {
                return back()->with('error', 'No backup floorplan found. Nothing to restore.');
            }

            DB::transaction(function () use ($map) {
                $current = $map->floorplan_image;
                $backup = $map->backup_floorplan_image;

                $map->floorplan_image = $backup;
                $map->backup_floorplan_image = $current;
                $map->save();
            });

            return back()->with('success', 'Previous indoor map restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }
    }

    public function updateIndoorMap(Request $request, IndoorMap $map)
    {
        $request->validate([
            'building_id'     => 'required|exists:buildings,id',
            'floor_number'    => 'required|integer|min:0',
            'floor_label'     => 'nullable|string|max:20',
            'name'            => 'nullable|string|max:255',
            'floorplan_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'geometry_file'   => 'nullable|file|mimes:json,geojson',
        ]);

        try {
            $building = Building::findOrFail($request->building_id);

            $floorNumber = (int) $request->floor_number;

            $map->building_id = $building->id;
            $map->floor_number = $floorNumber;
            $map->floor_label = $request->floor_label ?: ($floorNumber === 0 ? 'Basement' : $floorNumber . 'F');
            $map->name = $request->name ?: ($building->name . ' - ' . $map->floor_label);

            if ($request->hasFile('geometry_file')) {
                $geometry = $this->extractGeometryFromUpload($request, 'geometry_file');

                if (!$geometry) {
                    return back()->with('error', 'Invalid GeoJSON file. No Polygon or MultiPolygon geometry found.');
                }

                $map->geometry = $geometry;
            }

            if ($request->hasFile('floorplan_image')) {
                $folderPath = public_path('floorplan_image');

                if (!File::exists($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                }

                if ($map->floorplan_image) {
                    $map->backup_floorplan_image = $map->floorplan_image;
                }

                $buildingSlug = Str::slug($building->name, '_');
                $extension = $request->file('floorplan_image')->getClientOriginalExtension();
                $fileName = $buildingSlug . '_floor_' . $request->floor_number . '.' . $extension;

                $request->file('floorplan_image')->move($folderPath, $fileName);

                $imageInfo = getimagesize(public_path('floorplan_image/' . $fileName));
                $map->width = $imageInfo[0] ?? null;
                $map->height = $imageInfo[1] ?? null;
                $map->floorplan_image = $fileName;
            }

            $map->save();

            return back()->with('success', 'Indoor map updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract geometry from uploaded GeoJSON file.
     */
    private function extractGeometryFromUpload(Request $request, string $fieldName): ?array
    {
        if (!$request->hasFile($fieldName)) {
            return null;
        }

        $geojson = file_get_contents($request->file($fieldName)->getRealPath());
        $decoded = json_decode($geojson, true);

        if (!$decoded) {
            return null;
        }

        // FeatureCollection
        if (($decoded['type'] ?? null) === 'FeatureCollection') {
            $feature = $decoded['features'][0] ?? null;
            $geometry = $feature['geometry'] ?? null;

            if (in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'])) {
                return $geometry;
            }

            return null;
        }

        // Feature
        if (($decoded['type'] ?? null) === 'Feature') {
            $geometry = $decoded['geometry'] ?? null;

            if (in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'])) {
                return $geometry;
            }

            return null;
        }

        // Direct geometry
        if (in_array($decoded['type'] ?? null, ['Polygon', 'MultiPolygon'])) {
            return $decoded;
        }

        return null;
    }
}
