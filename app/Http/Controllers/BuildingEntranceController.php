<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\BuildingEntrance;
use Illuminate\Http\Request;

class BuildingEntranceController extends Controller
{
    public function BuildingEntrance()
    {
        $buildings = Building::select('id', 'name', 'geometry', 'color')
            ->orderBy('name')
            ->get();

        $buildingFeatures = $buildings->map(function ($building) {
            return [
                'type' => 'Feature',
                'geometry' => $building->geometry,
                'properties' => [
                    'id'    => $building->id,
                    'name'  => $building->name,
                    'color' => $building->color ?? '#2b82cc',
                ],
            ];
        })->values();

        $entrances = BuildingEntrance::with('building')
            ->latest()
            ->paginate(10);

        $entrancesMap = BuildingEntrance::with('building')
            ->get()
            ->map(function ($entrance) {
                return [
                    'id' => $entrance->id,
                    'name' => $entrance->name,
                    'is_primary' => (bool) $entrance->is_primary,
                    'latitude' => (float) $entrance->latitude,
                    'longitude' => (float) $entrance->longitude,
                    'building_name' => optional($entrance->building)->name,
                    'building_id' => $entrance->building_id,
                ];
            });

        return view('admin.building_entrances.building_entrances', [
            'buildingGeoJson' => [
                'type' => 'FeatureCollection',
                'features' => $buildingFeatures,
            ],
            'entrances' => $entrances,
            'entrancesMap' => $entrancesMap,
            'buildings' => $buildings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            BuildingEntrance::where('building_id', $validated['building_id'])
                ->update(['is_primary' => false]);
        }

        BuildingEntrance::create([
            'building_id' => $validated['building_id'],
            'name' => $validated['name'] ?? null,
            'is_primary' => $isPrimary,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return redirect()
            ->route('admin.building-entrances')
            ->with('success', 'Building entrance added successfully.');
    }

    public function update(Request $request, BuildingEntrance $buildingEntrance)
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            BuildingEntrance::where('building_id', $validated['building_id'])
                ->where('id', '!=', $buildingEntrance->id)
                ->update(['is_primary' => false]);
        }

        $buildingEntrance->update([
            'building_id' => $validated['building_id'],
            'name' => $validated['name'] ?? null,
            'is_primary' => $isPrimary,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return redirect()
            ->route('admin.building-entrances')
            ->with('success', 'Building entrance updated successfully.');
    }

    public function destroy(BuildingEntrance $buildingEntrance)
    {
        $buildingEntrance->delete();

        return redirect()
            ->route('admin.building-entrances')
            ->with('success', 'Building entrance deleted successfully.');
    }
}
