<?php

namespace App\Http\Controllers;

use App\Models\EntryPoint;
use App\Rules\ValidGeoJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EntryPointController extends Controller
{
    public function EntryPoint()
    {
        $entryPoints = EntryPoint::latest()->paginate(10);

        return view('admin.Entry_point.Entry_point', compact('entryPoints'));
    }

    public function uploadEntryPoint(Request $request)
    {
        $request->validate([
            'geojson' => ['required', 'file', 'mimes:json,geojson,txt', new ValidGeoJson(['Point'])],
        ]);

        try {
            $file = $request->file('geojson');
            $content = file_get_contents($file->getRealPath());
            $geojson = json_decode($content, true);

            if (!$this->isValidGeoJson($geojson)) {
                return back()->with('error', 'Invalid GeoJSON format.');
            }

            $folderPath = public_path('EntryPoints');
            $currentFile = $folderPath . '/entry_points.geojson';
            $backupFile = $folderPath . '/entry_points_backup.geojson';

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            // backup old
            if (File::exists($currentFile)) {
                File::copy($currentFile, $backupFile);
            }

            // replace file
            if (File::exists($currentFile)) {
                File::delete($currentFile);
            }

            $file->move($folderPath, 'entry_points.geojson');

            $saved = json_decode(File::get($currentFile), true);

            DB::transaction(function () use ($saved) {
                EntryPoint::query()->delete();

                foreach ($saved['features'] as $feature) {

                    if (!isset($feature['geometry'])) continue;

                    EntryPoint::create([
                        'name' => $feature['properties']['name'] ?? 'No Name',
                        'geometry' => $feature['geometry'],
                    ]);
                }
            });

            return back()->with('success', 'Entry points uploaded successfully.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetEntryPoint()
    {
        try {
            $folderPath = public_path('EntryPoints');
            $currentFile = $folderPath . '/entry_points.geojson';
            $backupFile = $folderPath . '/entry_points_backup.geojson';

            if (!File::exists($backupFile)) {
                return back()->with('error', 'No backup found.');
            }

            $backup = json_decode(File::get($backupFile), true);

            DB::transaction(function () use ($backup) {
                EntryPoint::query()->delete();

                foreach ($backup['features'] as $feature) {

                    if (!isset($feature['geometry'])) continue;

                    EntryPoint::create([
                        'name' => $feature['properties']['name'] ?? 'No Name',
                        'geometry' => $feature['geometry'],
                    ]);
                }
            });

            if (File::exists($currentFile)) {
                File::delete($currentFile);
            }

            File::copy($backupFile, $currentFile);

            return back()->with('success', 'Restored previous entry points.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateName(Request $request, EntryPoint $entry_point)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $entry_point->name = trim($request->name);
        $entry_point->save();

        return response()->json([
            'success' => true,
            'name' => $entry_point->name,
        ]);
    }

    private function isValidGeoJson($geojson)
    {
        return is_array($geojson)
            && isset($geojson['type'])
            && $geojson['type'] === 'FeatureCollection'
            && isset($geojson['features']);
    }
}
