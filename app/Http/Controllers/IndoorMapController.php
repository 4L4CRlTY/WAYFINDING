<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\IndoorMap;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class IndoorMapController extends Controller
{
    public function IndoorMap(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);
        $normalizedSearch = strtolower($search);

        $buildings = Building::orderBy('name')->get();
        $maps = IndoorMap::with('building')
            ->when($search !== '', function ($query) use ($search, $pattern, $normalizedSearch) {
                $query->where(function ($searchQuery) use ($search, $pattern, $normalizedSearch) {
                    $searchQuery->where('name', 'LIKE', $pattern)
                        ->orWhere('floor_label', 'LIKE', $pattern)
                        ->orWhere('floorplan_image', 'LIKE', $pattern)
                        ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                            $buildingQuery->where(function ($buildingSearchQuery) use ($pattern) {
                                $buildingSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('color', 'LIKE', $pattern);
                            });
                        });

                    if (is_numeric($search)) {
                        $numericSearch = (int) $search;
                        $searchQuery->orWhere('id', $numericSearch)
                            ->orWhere('floor_number', $numericSearch);
                    }

                    if (in_array($normalizedSearch, ['active', 'enabled'], true)) {
                        $searchQuery->orWhere('is_active', true);
                    }

                    if (in_array($normalizedSearch, ['inactive', 'disabled'], true)) {
                        $searchQuery->orWhere('is_active', false);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.indoor_map.indoor_map', compact('buildings', 'maps', 'search'));
    }

    public function uploadIndoorMap(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0',
            'floor_label' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
            'floorplan_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'geometry_file' => ['required', 'file', 'mimes:json,geojson', new ValidGeoJson(['Polygon', 'MultiPolygon'], false)],
        ]);

        try {
            $building = Building::findOrFail($request->building_id);
            $floorNumber = (int) $request->floor_number;
            $floorLabel = $request->floor_label ?: ($floorNumber === 0 ? 'Basement' : $floorNumber.'F');
            $name = $request->name ?: ($building->name.' - '.$floorLabel);

            $folderPath = public_path('floorplan_image');

            if (! File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            $geometry = $this->extractGeometryFromUpload($request, 'geometry_file');

            if (! $geometry) {
                return back()->with('error', 'Invalid GeoJSON file. No Polygon or MultiPolygon geometry found.');
            }

            $imageInfo = getimagesize($request->file('floorplan_image')->getRealPath());
            $imageWidth = $imageInfo[0] ?? null;
            $imageHeight = $imageInfo[1] ?? null;

            $buildingSlug = Str::slug($building->name, '_');
            $extension = $request->file('floorplan_image')->getClientOriginalExtension();
            $fileName = $buildingSlug.'_floor_'.$floorNumber.'.'.$extension;

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
                    $backupFileName = $this->copyCurrentFloorplanToBackup(
                        $existingMap,
                        $building,
                        $floorNumber,
                    );

                    if ($backupFileName) {
                        $existingMap->backup_floorplan_image = $backupFileName;
                        $existingMap->save();
                    }
                }

                $request->file('floorplan_image')->move($folderPath, $fileName);

                if ($existingMap) {
                    $existingMap->update([
                        'name' => $name,
                        'floor_number' => $floorNumber,
                        'floor_label' => $floorLabel,
                        'floorplan_image' => $fileName,
                        'width' => $imageWidth,
                        'height' => $imageHeight,
                        'geometry' => $geometry,
                        'is_active' => true,
                    ]);
                } else {
                    IndoorMap::create([
                        'building_id' => $building->id,
                        'name' => $name,
                        'floor_number' => $floorNumber,
                        'floor_label' => $floorLabel,
                        'floorplan_image' => $fileName,
                        'backup_floorplan_image' => null,
                        'width' => $imageWidth,
                        'height' => $imageHeight,
                        'geometry' => $geometry,
                        'is_active' => true,
                    ]);
                }
            });

            $this->storeExactGeometryUpload(
                $request->file('geometry_file'),
                $building,
                $floorLabel,
            );

            return back()->with('success', 'Indoor map uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }

    public function resetIndoorMap(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0',
        ]);

        try {
            $map = IndoorMap::where('building_id', $request->building_id)
                ->where('floor_number', $request->floor_number)
                ->first();

            if (! $map) {
                return back()->with('error', 'Indoor map not found for the selected building and floor.');
            }

            if (! $map->backup_floorplan_image) {
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
            return back()->with('error', 'Reset failed: '.$e->getMessage());
        }
    }

    public function updateIndoorMap(Request $request, IndoorMap $map)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0',
            'floor_label' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
            'floorplan_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'geometry_file' => ['nullable', 'file', 'mimes:json,geojson', new ValidGeoJson(['Polygon', 'MultiPolygon'], false)],
        ]);

        try {
            $building = Building::findOrFail($request->building_id);

            $floorNumber = (int) $request->floor_number;

            $map->building_id = $building->id;
            $map->floor_number = $floorNumber;
            $map->floor_label = $request->floor_label ?: ($floorNumber === 0 ? 'Basement' : $floorNumber.'F');
            $map->name = $request->name ?: ($building->name.' - '.$map->floor_label);

            if ($request->hasFile('geometry_file')) {
                $geometry = $this->extractGeometryFromUpload($request, 'geometry_file');

                if (! $geometry) {
                    return back()->with('error', 'Invalid GeoJSON file. No Polygon or MultiPolygon geometry found.');
                }

                $map->geometry = $geometry;
                $this->storeExactGeometryUpload(
                    $request->file('geometry_file'),
                    $building,
                    $map->floor_label,
                );
            }

            if ($request->hasFile('floorplan_image')) {
                $folderPath = public_path('floorplan_image');

                if (! File::exists($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                }

                if ($map->floorplan_image) {
                    $backupFileName = $this->copyCurrentFloorplanToBackup(
                        $map,
                        $building,
                        $floorNumber,
                    );

                    if ($backupFileName) {
                        $map->backup_floorplan_image = $backupFileName;
                    }
                }

                $buildingSlug = Str::slug($building->name, '_');
                $extension = $request->file('floorplan_image')->getClientOriginalExtension();
                $fileName = $buildingSlug.'_floor_'.$request->floor_number.'.'.$extension;

                $request->file('floorplan_image')->move($folderPath, $fileName);

                $imageInfo = getimagesize(public_path('floorplan_image/'.$fileName));
                $map->width = $imageInfo[0] ?? null;
                $map->height = $imageInfo[1] ?? null;
                $map->floorplan_image = $fileName;
            }

            $map->save();

            return back()->with('success', 'Indoor map updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    /**
     * Extract geometry from uploaded GeoJSON file.
     */
    private function extractGeometryFromUpload(Request $request, string $fieldName): ?array
    {
        if (! $request->hasFile($fieldName)) {
            return null;
        }

        $geojson = file_get_contents($request->file($fieldName)->getRealPath());
        $decoded = json_decode($geojson, true);

        if (! $decoded) {
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

    private function storeExactGeometryUpload(
        \Illuminate\Http\UploadedFile $file,
        Building $building,
        string $floorLabel,
    ): void {
        $buildingSlug = Str::slug($building->name, '_');
        $normalizedFloor = strtolower(Str::slug($floorLabel, ''));
        $folderPath = public_path("indoor_maps/{$buildingSlug}/{$normalizedFloor}");
        $currentFile = $folderPath.DIRECTORY_SEPARATOR.'indoor_map.geojson';
        $backupFile = $folderPath.DIRECTORY_SEPARATOR.'indoor_map_backup.geojson';

        if (! File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        if (File::isFile($currentFile)) {
            File::copy($currentFile, $backupFile);
        }

        File::put($currentFile, File::get($file->getRealPath()));
    }

    private function copyCurrentFloorplanToBackup(
        IndoorMap $map,
        Building $building,
        int $floorNumber,
    ): ?string {
        if (! $map->floorplan_image) {
            return null;
        }

        $sourcePath = public_path('floorplan_image/'.$map->floorplan_image);

        if (! File::isFile($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'png';
        $buildingSlug = Str::slug($building->name, '_');
        $backupFileName = sprintf(
            '%s_floor_%d_backup_%s.%s',
            $buildingSlug,
            $floorNumber,
            now()->format('Ymd_Hisu'),
            strtolower($extension),
        );
        $backupPath = public_path('floorplan_image/'.$backupFileName);

        if (! File::copy($sourcePath, $backupPath)) {
            throw new \RuntimeException('Unable to preserve the previous floorplan image.');
        }

        return $backupFileName;
    }
}
