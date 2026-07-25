<?php

namespace App\Http\Controllers;

use App\Models\Landuse;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LandUseController extends Controller
{
    public function LandUse()
    {
        $landuses = Landuse::latest()->paginate(10);

        return view('admin.landuse.landuse', compact('landuses'));
    }

    public function uploadLandUse(Request $request)
    {
        $request->validate([
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['Polygon', 'MultiPolygon'])],
            'default_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'default_image_width' => 'nullable|integer|min:20|max:2000',
            'default_image_height' => 'nullable|integer|min:20|max:2000',
            'default_image_rotation' => 'nullable|integer|min:-360|max:360',
            'default_image_offset_x' => 'nullable|integer|min:-5000|max:5000',
            'default_image_offset_y' => 'nullable|integer|min:-5000|max:5000',
        ]);

        try {
            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (!$this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON.');
            }

            $folder = public_path('Landuses');
            $current = $folder . '/landuse.geojson';
            $backup = $folder . '/landuse_backup.geojson';

            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0755, true);
            }

            if (File::exists($current)) {
                File::copy($current, $backup);
                File::delete($current);
            }

            $file->move($folder, 'landuse.geojson');

            $saved = json_decode(File::get($current), true);

            $defaultImageName = null;

            if ($request->hasFile('default_image')) {
                $imageFolder = public_path('landuse_images');

                if (!File::exists($imageFolder)) {
                    File::makeDirectory($imageFolder, 0755, true);
                }

                $imageFile = $request->file('default_image');
                $defaultImageName = time() . '_' . preg_replace('/\s+/', '_', $imageFile->getClientOriginalName());
                $imageFile->move($imageFolder, $defaultImageName);
            }

            $defaultWidth = (int) ($request->default_image_width ?? 120);
            $defaultHeight = (int) ($request->default_image_height ?? 120);
            $defaultRotation = (int) ($request->default_image_rotation ?? 0);
            $defaultOffsetX = (int) ($request->default_image_offset_x ?? 0);
            $defaultOffsetY = (int) ($request->default_image_offset_y ?? 0);

            DB::transaction(function () use (
                $saved,
                $defaultImageName,
                $defaultWidth,
                $defaultHeight,
                $defaultRotation,
                $defaultOffsetX,
                $defaultOffsetY
            ) {
                Landuse::query()->delete();

                foreach ($saved['features'] as $feature) {
                    if (!isset($feature['geometry'])) {
                        continue;
                    }

                    $properties = $feature['properties'] ?? [];

                    Landuse::create([
                        'name' => $properties['name'] ?? 'No Name',
                        'geometry' => $feature['geometry'],
                        'properties' => $properties,

                        // basic
                        'image' => $defaultImageName,
                        'image_width' => $defaultWidth,
                        'image_height' => $defaultHeight,
                        'image_rotation' => $defaultRotation,
                        'image_offset_x' => $defaultOffsetX,
                        'image_offset_y' => $defaultOffsetY,

                        // normalized
                        'image_scale_x' => 1.0000,
                        'image_scale_y' => 1.0000,
                        'image_offset_x_ratio' => 0.0000,
                        'image_offset_y_ratio' => 0.0000,

                        // polygon-aware
                        'polygon_base_angle' => 0.0000,
                        'image_local_scale_x' => 1.0000,
                        'image_local_scale_y' => 1.0000,
                        'image_local_offset_u' => 0.0000,
                        'image_local_offset_v' => 0.0000,
                        'image_local_rotation' => 0.0000,

                        // exact 4-corner anchors
                        'image_tl_lat' => null,
                        'image_tl_lng' => null,
                        'image_tr_lat' => null,
                        'image_tr_lng' => null,
                        'image_bl_lat' => null,
                        'image_bl_lng' => null,
                        'image_br_lat' => null,
                        'image_br_lng' => null,
                    ]);
                }
            });

            return back()->with('success', 'Landuse uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetLandUse()
    {
        try {
            $folder = public_path('Landuses');
            $current = $folder . '/landuse.geojson';
            $backup = $folder . '/landuse_backup.geojson';

            if (!File::exists($backup)) {
                return back()->with('error', 'No backup found.');
            }

            $data = json_decode(File::get($backup), true);

            DB::transaction(function () use ($data) {
                Landuse::query()->delete();

                foreach ($data['features'] as $feature) {
                    if (!isset($feature['geometry'])) {
                        continue;
                    }

                    $properties = $feature['properties'] ?? [];

                    Landuse::create([
                        'name' => $properties['name'] ?? 'No Name',
                        'geometry' => $feature['geometry'],
                        'properties' => $properties,

                        // basic
                        'image' => null,
                        'image_width' => 120,
                        'image_height' => 120,
                        'image_rotation' => 0,
                        'image_offset_x' => 0,
                        'image_offset_y' => 0,

                        // normalized
                        'image_scale_x' => 1.0000,
                        'image_scale_y' => 1.0000,
                        'image_offset_x_ratio' => 0.0000,
                        'image_offset_y_ratio' => 0.0000,

                        // polygon-aware
                        'polygon_base_angle' => 0.0000,
                        'image_local_scale_x' => 1.0000,
                        'image_local_scale_y' => 1.0000,
                        'image_local_offset_u' => 0.0000,
                        'image_local_offset_v' => 0.0000,
                        'image_local_rotation' => 0.0000,

                        // exact 4-corner anchors
                        'image_tl_lat' => null,
                        'image_tl_lng' => null,
                        'image_tr_lat' => null,
                        'image_tr_lng' => null,
                        'image_bl_lat' => null,
                        'image_bl_lng' => null,
                        'image_br_lat' => null,
                        'image_br_lng' => null,
                    ]);
                }
            });

            if (File::exists($current)) {
                File::delete($current);
            }

            File::copy($backup, $current);

            return back()->with('success', 'Landuse restored.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateName(Request $request, Landuse $landuse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $landuse->name = trim($request->name);

        $props = $landuse->properties ?? [];
        $props['name'] = $landuse->name;
        $landuse->properties = $props;

        $landuse->save();

        return response()->json([
            'success' => true,
            'name' => $landuse->name,
        ]);
    }

    public function updateImage(Request $request, Landuse $landuse)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'image_width' => 'required|integer|min:20|max:2000',
            'image_height' => 'required|integer|min:20|max:2000',
            'image_rotation' => 'required|integer|min:-360|max:360',
            'image_offset_x' => 'nullable|integer|min:-5000|max:5000',
            'image_offset_y' => 'nullable|integer|min:-5000|max:5000',
        ]);

        try {
            $imageName = $landuse->image;

            if ($request->hasFile('image')) {
                $imageFolder = public_path('landuse_images');

                if (!File::exists($imageFolder)) {
                    File::makeDirectory($imageFolder, 0755, true);
                }

                if ($landuse->image && File::exists($imageFolder . '/' . $landuse->image)) {
                    File::delete($imageFolder . '/' . $landuse->image);
                }

                $file = $request->file('image');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move($imageFolder, $imageName);
            }

            $landuse->update([
                'image' => $imageName,
                'image_width' => (int) $request->image_width,
                'image_height' => (int) $request->image_height,
                'image_rotation' => (int) $request->image_rotation,
                'image_offset_x' => (int) ($request->image_offset_x ?? 0),
                'image_offset_y' => (int) ($request->image_offset_y ?? 0),
            ]);

            return back()->with('success', 'Landuse image updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateEditor(Request $request, Landuse $landuse)
    {
        $request->validate([
            'image_width' => 'required|integer|min:20|max:2000',
            'image_height' => 'required|integer|min:20|max:2000',
            'image_rotation' => 'required|numeric|min:-360|max:360',
            'image_offset_x' => 'required|integer|min:-5000|max:5000',
            'image_offset_y' => 'required|integer|min:-5000|max:5000',

            'image_scale_x' => 'required|numeric|min:0.01|max:20',
            'image_scale_y' => 'required|numeric|min:0.01|max:20',
            'image_offset_x_ratio' => 'required|numeric|min:-5|max:5',
            'image_offset_y_ratio' => 'required|numeric|min:-5|max:5',

            'polygon_base_angle' => 'required|numeric|min:-360|max:360',
            'image_local_scale_x' => 'required|numeric|min:0.01|max:20',
            'image_local_scale_y' => 'required|numeric|min:0.01|max:20',
            'image_local_offset_u' => 'required|numeric|min:-5|max:5',
            'image_local_offset_v' => 'required|numeric|min:-5|max:5',
            'image_local_rotation' => 'required|numeric|min:-360|max:360',

            'image_tl_lat' => 'nullable|numeric',
            'image_tl_lng' => 'nullable|numeric',
            'image_tr_lat' => 'nullable|numeric',
            'image_tr_lng' => 'nullable|numeric',
            'image_bl_lat' => 'nullable|numeric',
            'image_bl_lng' => 'nullable|numeric',
            'image_br_lat' => 'nullable|numeric',
            'image_br_lng' => 'nullable|numeric',
        ]);

        try {
            $landuse->update([
                // basic
                'image_width' => (int) $request->image_width,
                'image_height' => (int) $request->image_height,
                'image_rotation' => (int) round((float) $request->image_rotation),
                'image_offset_x' => (int) $request->image_offset_x,
                'image_offset_y' => (int) $request->image_offset_y,

                // normalized
                'image_scale_x' => round((float) $request->image_scale_x, 4),
                'image_scale_y' => round((float) $request->image_scale_y, 4),
                'image_offset_x_ratio' => round((float) $request->image_offset_x_ratio, 4),
                'image_offset_y_ratio' => round((float) $request->image_offset_y_ratio, 4),

                // polygon-aware
                'polygon_base_angle' => round((float) $request->polygon_base_angle, 4),
                'image_local_scale_x' => round((float) $request->image_local_scale_x, 4),
                'image_local_scale_y' => round((float) $request->image_local_scale_y, 4),
                'image_local_offset_u' => round((float) $request->image_local_offset_u, 4),
                'image_local_offset_v' => round((float) $request->image_local_offset_v, 4),
                'image_local_rotation' => round((float) $request->image_local_rotation, 4),

                // exact 4-corner anchors
                'image_tl_lat' => $request->filled('image_tl_lat') ? round((float) $request->image_tl_lat, 8) : null,
                'image_tl_lng' => $request->filled('image_tl_lng') ? round((float) $request->image_tl_lng, 8) : null,
                'image_tr_lat' => $request->filled('image_tr_lat') ? round((float) $request->image_tr_lat, 8) : null,
                'image_tr_lng' => $request->filled('image_tr_lng') ? round((float) $request->image_tr_lng, 8) : null,
                'image_bl_lat' => $request->filled('image_bl_lat') ? round((float) $request->image_bl_lat, 8) : null,
                'image_bl_lng' => $request->filled('image_bl_lng') ? round((float) $request->image_bl_lng, 8) : null,
                'image_br_lat' => $request->filled('image_br_lat') ? round((float) $request->image_br_lat, 8) : null,
                'image_br_lng' => $request->filled('image_br_lng') ? round((float) $request->image_br_lng, 8) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Editor settings saved successfully.',
                'data' => [
                    'image_width' => $landuse->image_width,
                    'image_height' => $landuse->image_height,
                    'image_rotation' => $landuse->image_rotation,
                    'image_offset_x' => $landuse->image_offset_x,
                    'image_offset_y' => $landuse->image_offset_y,

                    'image_scale_x' => $landuse->image_scale_x,
                    'image_scale_y' => $landuse->image_scale_y,
                    'image_offset_x_ratio' => $landuse->image_offset_x_ratio,
                    'image_offset_y_ratio' => $landuse->image_offset_y_ratio,

                    'polygon_base_angle' => $landuse->polygon_base_angle,
                    'image_local_scale_x' => $landuse->image_local_scale_x,
                    'image_local_scale_y' => $landuse->image_local_scale_y,
                    'image_local_offset_u' => $landuse->image_local_offset_u,
                    'image_local_offset_v' => $landuse->image_local_offset_v,
                    'image_local_rotation' => $landuse->image_local_rotation,

                    'image_tl_lat' => $landuse->image_tl_lat,
                    'image_tl_lng' => $landuse->image_tl_lng,
                    'image_tr_lat' => $landuse->image_tr_lat,
                    'image_tr_lng' => $landuse->image_tr_lng,
                    'image_bl_lat' => $landuse->image_bl_lat,
                    'image_bl_lng' => $landuse->image_bl_lng,
                    'image_br_lat' => $landuse->image_br_lat,
                    'image_br_lng' => $landuse->image_br_lng,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function isValidGeoJson($geojson)
    {
        return is_array($geojson)
            && isset($geojson['type'])
            && $geojson['type'] === 'FeatureCollection'
            && isset($geojson['features']);
    }
}
