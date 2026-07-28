<?php

namespace App\Http\Controllers;

use App\Models\DestinationLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DestinationLinkController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->tableSearch($request);
        $pattern = $this->tableSearchPattern($search);

        $destinationLinks = DestinationLink::with([
            'building',
            'indoorRoom.indoorMap.building',
            'landuse',
            'campusEvent',
            'creator',
        ])
            ->when($search !== '', function ($query) use ($pattern) {
                $query->where(function ($searchQuery) use ($pattern) {
                    $searchQuery->where('title', 'LIKE', $pattern)
                        ->orWhere('token', 'LIKE', $pattern)
                        ->orWhere('destination_type', 'LIKE', $pattern)
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'building')
                                ->whereHas('building', fn ($q) => $q->where('name', 'LIKE', $pattern));
                        })
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'room')
                                ->whereHas('indoorRoom', function ($q) use ($pattern) {
                                    $q->where('name', 'LIKE', $pattern)
                                        ->orWhere('room_code', 'LIKE', $pattern)
                                        ->orWhereHas('indoorMap.building', fn ($buildingQuery) => $buildingQuery->where('name', 'LIKE', $pattern));
                                });
                        })
                        ->orWhere(function ($destinationQuery) use ($pattern) {
                            $destinationQuery->where('destination_type', 'landuse')
                                ->whereHas('landuse', fn ($q) => $q->where('name', 'LIKE', $pattern));
                        })
                        ->orWhereHas('campusEvent', fn ($q) => $q->where('title', 'LIKE', $pattern));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.destination_links.index', compact(
            'destinationLinks',
            'search',
        ));
    }

    public function toggle(DestinationLink $destinationLink)
    {
        $destinationLink->loadMissing('campusEvent');

        if (! $destinationLink->is_active
            && $destinationLink->campusEvent
            && (! $destinationLink->campusEvent->is_active
                || ($destinationLink->campusEvent->ends_at && $destinationLink->campusEvent->ends_at->isPast()))) {
            return back()->with('error', 'Reactivate the campus event before enabling its route link.');
        }

        $destinationLink->update(['is_active' => ! $destinationLink->is_active]);

        return back()->with('success', 'Destination link status updated.');
    }

    public function destroy(DestinationLink $destinationLink)
    {
        $destinationLink->delete();

        return back()->with('success', 'Destination link deleted.');
    }

    public function open(DestinationLink $destinationLink)
    {
        $destinationLink->loadMissing('campusEvent');
        abort_unless($destinationLink->isAvailable(), 404);

        $destinationLink->loadMissing('building', 'indoorRoom.indoorMap.building', 'landuse');
        abort_if($destinationLink->destination() === null, 404);

        if (Auth::check() && Auth::user()?->role === 'user') {
            return redirect()->route('user.dashboard', ['destination_link' => $destinationLink->token]);
        }

        return app(UserController::class)->GuestDashboard($destinationLink);
    }
}
