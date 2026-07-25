<?php

namespace App\Http\Controllers;

use App\Models\HazardPoint;
use App\Models\Path;
use Illuminate\Http\Request;

class HazardPointController extends Controller
{
    public function HazardPoint()
    {
        $paths = Path::select('id', 'name', 'geometry', 'type')->get();

        $pathFeatures = $paths->map(function ($path) {
            return [
                'type' => 'Feature',
                'geometry' => $path->geometry,
                'properties' => [
                    'id' => $path->id,
                    'name' => $path->name,
                    'type' => $path->type,
                ],
            ];
        })->values();

        $hazardPoints = HazardPoint::with('path')
            ->latest()
            ->paginate(10);

        $hazardPointsMap = HazardPoint::with('path')
            ->where('is_active', true)
            ->get()
            ->map(function ($hazard) {
                return [
                    'id' => $hazard->id,
                    'path_id' => $hazard->path_id,
                    'path_name' => optional($hazard->path)->name,
                    'title' => $hazard->title,
                    'description' => $hazard->description,
                    'warning_type' => $hazard->warning_type,
                    'severity_level' => (int) $hazard->severity_level,
                    'latitude' => (float) $hazard->latitude,
                    'longitude' => (float) $hazard->longitude,
                    'affects_routing' => (bool) $hazard->affects_routing,
                    'is_active' => (bool) $hazard->is_active,
                ];
            });

        return view('admin.hazard_point.hazard_point', [
            'pathFeatures' => $pathFeatures,
            'hazardPoints' => $hazardPoints,
            'hazardPointsMap' => $hazardPointsMap,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'path_id' => 'required|exists:paths,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'warning_type' => 'required|string|in:hazard,broken_road,slippery,stairs,construction,flood,caution',
            'severity_level' => 'required|integer|min:1|max:3',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'affects_routing' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        HazardPoint::create($validated);

        return back()->with('success', 'Hazard point saved successfully.');
    }

    public function update(Request $request, HazardPoint $hazardPoint)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'warning_type' => 'required|string|in:hazard,broken_road,slippery,stairs,construction,flood,caution',
            'severity_level' => 'required|integer|min:1|max:3',
            'affects_routing' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $hazardPoint->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hazard point updated successfully.',
            'title' => $hazardPoint->title,
            'description' => $hazardPoint->description,
            'warning_type' => $hazardPoint->warning_type,
            'severity_level' => (int) $hazardPoint->severity_level,
            'affects_routing' => (bool) $hazardPoint->affects_routing,
            'is_active' => (bool) $hazardPoint->is_active,
        ]);
    }

    public function destroy(HazardPoint $hazardPoint)
    {
        $hazardPoint->delete();

        return back()->with('success', 'Hazard point deleted successfully.');
    }
}
