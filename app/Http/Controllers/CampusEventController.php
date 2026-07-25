<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampusEventController extends Controller
{
    public function CampusEvent()
    {
        $buildings = Building::orderBy('name')->get();

        $indoorRooms = IndoorRoom::with('indoorMap.building')
            ->orderBy('room_code')
            ->orderBy('name')
            ->get();

        $landuses = Landuse::orderBy('name')->get();

        $campusEvents = CampusEvent::with([
                'building',
                'indoorRoom.indoorMap.building',
                'landuse',
                'creator',
            ])
            ->orderByDesc('starts_at')
            ->get();

        return view('admin.campus_event.campus_event', compact(
            'buildings',
            'indoorRooms',
            'landuses',
            'campusEvents'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_target_type' => ['required', 'in:building,room,landuse'],
            'building_id'       => ['nullable', 'exists:buildings,id'],
            'indoor_room_id'    => ['nullable', 'exists:indoor_rooms,id'],
            'landuse_id'        => ['nullable', 'exists:landuses,id'],
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'location_label'    => ['nullable', 'string', 'max:255'],
            'starts_at'         => ['required', 'date'],
            'ends_at'           => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'          => ['nullable', 'integer'],
        ]);

        $targetType = $request->event_target_type;

        $buildingId = null;
        $indoorRoomId = null;
        $landuseId = null;

        if ($targetType === 'building') {
            if (!$request->building_id) {
                return back()
                    ->withInput()
                    ->with('error', 'Please select a building for this event.');
            }

            $buildingId = $request->building_id;
        }

        if ($targetType === 'room') {
            if (!$request->indoor_room_id) {
                return back()
                    ->withInput()
                    ->with('error', 'Please select an indoor room for this event.');
            }

            $indoorRoomId = $request->indoor_room_id;
        }

        if ($targetType === 'landuse') {
            if (!$request->landuse_id) {
                return back()
                    ->withInput()
                    ->with('error', 'Please select a landuse area for this event.');
            }

            $landuseId = $request->landuse_id;
        }

        CampusEvent::create([
            'event_target_type' => $targetType,
            'building_id'       => $buildingId,
            'indoor_room_id'    => $indoorRoomId,
            'landuse_id'        => $landuseId,
            'created_by'        => Auth::id(),
            'title'             => $request->title,
            'description'       => $request->description,
            'starts_at'         => $request->starts_at,
            'ends_at'           => $request->ends_at,
            'location_label'    => $request->location_label,
            'is_active'         => $request->has('is_active'),
            'priority'          => $request->priority ?? 0,
        ]);

        return back()->with('success', 'Campus event created successfully.');
    }

    public function toggleStatus(CampusEvent $campusEvent)
    {
        $campusEvent->update([
            'is_active' => !$campusEvent->is_active,
        ]);

        return back()->with('success', 'Campus event status updated successfully.');
    }

    public function destroy(CampusEvent $campusEvent)
    {
        $campusEvent->delete();

        return back()->with('success', 'Campus event deleted successfully.');
    }
}
