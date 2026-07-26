<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\HazardPoint;
use App\Models\Path;
use Illuminate\Http\Request;

class HazardPointController extends Controller
{
    public function HazardPoint(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);
        $normalizedSearch = strtolower($search);

        $paths = Path::select('id', 'name', 'geometry', 'type')->get();
        $buildingFeatures = Building::select('id', 'name', 'geometry', 'color')
            ->orderBy('name')
            ->get()
            ->map(function ($building) {
                return [
                    'type' => 'Feature',
                    'geometry' => $building->geometry,
                    'properties' => [
                        'id' => $building->id,
                        'name' => $building->name,
                        'color' => $building->color,
                    ],
                ];
            })->values();

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
            ->when($search !== '', function ($query) use ($search, $pattern, $normalizedSearch) {
                $query->where(function ($searchQuery) use ($search, $pattern, $normalizedSearch) {
                    $searchQuery->where('title', 'LIKE', $pattern)
                        ->orWhere('description', 'LIKE', $pattern)
                        ->orWhere('warning_type', 'LIKE', $pattern)
                        ->orWhere('latitude', 'LIKE', $pattern)
                        ->orWhere('longitude', 'LIKE', $pattern)
                        ->orWhereHas('path', function ($pathQuery) use ($pattern) {
                            $pathQuery->where(function ($pathSearchQuery) use ($pattern) {
                                $pathSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('type', 'LIKE', $pattern)
                                    ->orWhere('hazard_note', 'LIKE', $pattern);
                            });
                        });

                    if (is_numeric($search)) {
                        $numericSearch = (int) $search;
                        $searchQuery->orWhere('id', $numericSearch)
                            ->orWhere('severity_level', $numericSearch);
                    }

                    if (in_array($normalizedSearch, ['active', 'enabled'], true)) {
                        $searchQuery->orWhere('is_active', true);
                    }

                    if (in_array($normalizedSearch, ['inactive', 'disabled'], true)) {
                        $searchQuery->orWhere('is_active', false);
                    }

                    if (in_array($normalizedSearch, ['routing', 'affects routing', 'yes'], true)) {
                        $searchQuery->orWhere('affects_routing', true);
                    }

                    if (in_array($normalizedSearch, ['non-routing', 'does not affect routing', 'no'], true)) {
                        $searchQuery->orWhere('affects_routing', false);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

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
            'buildingFeatures' => $buildingFeatures,
            'hazardPoints' => $hazardPoints,
            'hazardPointsMap' => $hazardPointsMap,
            'search' => $search,
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
