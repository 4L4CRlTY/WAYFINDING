<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\IndoorEntrance;
use App\Models\IndoorStairLink;
use Illuminate\Http\Request;

class IndoorStairsLink extends Controller
{
    public function IndoorStairsLink(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $buildings = Building::orderBy('name')->get();

        $stairsEntrances = IndoorEntrance::with('indoorMap.building')
            ->where('ent_type', 'stairs')
            ->whereHas('indoorMap')
            ->get();

        $links = IndoorStairLink::with([
            'building',
            'fromEntrance.indoorMap.building',
            'toEntrance.indoorMap.building',
        ])
            ->when($search !== '', function ($query) use ($search, $pattern) {
                $query->where(function ($searchQuery) use ($search, $pattern) {
                    $searchQuery->where('name', 'LIKE', $pattern)
                        ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                            $buildingQuery->where('name', 'LIKE', $pattern);
                        })
                        ->orWhereHas('fromEntrance', function ($entranceQuery) use ($search, $pattern) {
                            $entranceQuery->where(function ($entranceSearchQuery) use ($search, $pattern) {
                                $entranceSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('room_code', 'LIKE', $pattern)
                                    ->orWhere('ent_type', 'LIKE', $pattern)
                                    ->orWhereHas('indoorMap', function ($mapQuery) use ($search, $pattern) {
                                        $mapQuery->where(function ($mapSearchQuery) use ($search, $pattern) {
                                            $mapSearchQuery->where('name', 'LIKE', $pattern)
                                                ->orWhere('floor_label', 'LIKE', $pattern)
                                                ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                                                    $buildingQuery->where('name', 'LIKE', $pattern);
                                                });

                                            if (is_numeric($search)) {
                                                $mapSearchQuery->orWhere('floor_number', (int) $search);
                                            }
                                        });
                                    });
                            });
                        })
                        ->orWhereHas('toEntrance', function ($entranceQuery) use ($search, $pattern) {
                            $entranceQuery->where(function ($entranceSearchQuery) use ($search, $pattern) {
                                $entranceSearchQuery->where('name', 'LIKE', $pattern)
                                    ->orWhere('room_code', 'LIKE', $pattern)
                                    ->orWhere('ent_type', 'LIKE', $pattern)
                                    ->orWhereHas('indoorMap', function ($mapQuery) use ($search, $pattern) {
                                        $mapQuery->where(function ($mapSearchQuery) use ($search, $pattern) {
                                            $mapSearchQuery->where('name', 'LIKE', $pattern)
                                                ->orWhere('floor_label', 'LIKE', $pattern)
                                                ->orWhereHas('building', function ($buildingQuery) use ($pattern) {
                                                    $buildingQuery->where('name', 'LIKE', $pattern);
                                                });

                                            if (is_numeric($search)) {
                                                $mapSearchQuery->orWhere('floor_number', (int) $search);
                                            }
                                        });
                                    });
                            });
                        });

                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.indoor_stairs_link.indoor_stairs_link', compact(
            'buildings',
            'stairsEntrances',
            'links',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'from_entrance_id' => 'required|exists:indoor_entrances,id|different:to_entrance_id',
            'to_entrance_id' => 'required|exists:indoor_entrances,id',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $fromEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->from_entrance_id);
            $toEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->to_entrance_id);

            if ($fromEntrance->ent_type !== 'stairs' || $toEntrance->ent_type !== 'stairs') {
                return back()->withInput()->with('error', 'Both selected entrances must be stairs.');
            }

            if (! $fromEntrance->indoorMap || ! $toEntrance->indoorMap) {
                return back()->withInput()->with('error', 'Selected stairs entrances must belong to valid indoor maps.');
            }

            if ((int) $fromEntrance->indoorMap->building_id !== (int) $request->building_id ||
                (int) $toEntrance->indoorMap->building_id !== (int) $request->building_id) {
                return back()->withInput()->with('error', 'Selected stairs entrances must belong to the selected building.');
            }

            $existing = IndoorStairLink::where('building_id', $request->building_id)
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('from_entrance_id', $request->from_entrance_id)
                            ->where('to_entrance_id', $request->to_entrance_id);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('from_entrance_id', $request->to_entrance_id)
                            ->where('to_entrance_id', $request->from_entrance_id);
                    });
                })
                ->first();

            if ($existing) {
                return back()->withInput()->with('error', 'This stairs link already exists.');
            }

            IndoorStairLink::create([
                'building_id' => $request->building_id,
                'from_entrance_id' => $request->from_entrance_id,
                'to_entrance_id' => $request->to_entrance_id,
                'name' => $request->name,
            ]);

            return back()->with('success', 'Indoor stairs link created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Create failed: '.$e->getMessage());
        }
    }

    public function update(Request $request, IndoorStairLink $link)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'from_entrance_id' => 'required|exists:indoor_entrances,id|different:to_entrance_id',
            'to_entrance_id' => 'required|exists:indoor_entrances,id',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $fromEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->from_entrance_id);
            $toEntrance = IndoorEntrance::with('indoorMap')->findOrFail($request->to_entrance_id);

            if ($fromEntrance->ent_type !== 'stairs' || $toEntrance->ent_type !== 'stairs') {
                return back()->withInput()->with('error', 'Both selected entrances must be stairs.');
            }

            if (! $fromEntrance->indoorMap || ! $toEntrance->indoorMap) {
                return back()->withInput()->with('error', 'Selected stairs entrances must belong to valid indoor maps.');
            }

            if ((int) $fromEntrance->indoorMap->building_id !== (int) $request->building_id ||
                (int) $toEntrance->indoorMap->building_id !== (int) $request->building_id) {
                return back()->withInput()->with('error', 'Selected stairs entrances must belong to the selected building.');
            }

            $existing = IndoorStairLink::where('id', '!=', $link->id)
                ->where('building_id', $request->building_id)
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('from_entrance_id', $request->from_entrance_id)
                            ->where('to_entrance_id', $request->to_entrance_id);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('from_entrance_id', $request->to_entrance_id)
                            ->where('to_entrance_id', $request->from_entrance_id);
                    });
                })
                ->first();

            if ($existing) {
                return back()->withInput()->with('error', 'This stairs link already exists.');
            }

            $link->update([
                'building_id' => $request->building_id,
                'from_entrance_id' => $request->from_entrance_id,
                'to_entrance_id' => $request->to_entrance_id,
                'name' => $request->name,
            ]);

            return back()->with('success', 'Indoor stairs link updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    public function destroy(IndoorStairLink $link)
    {
        try {
            $link->delete();

            return back()->with('success', 'Indoor stairs link deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }
}
