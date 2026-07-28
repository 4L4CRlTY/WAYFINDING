<?php

namespace App\Http\Controllers;

use App\Models\DestinationLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function UserDashboard(Request $request)
    {
        $destinationLink = null;

        if ($request->filled('destination_link')) {
            $destinationLink = DestinationLink::with('building', 'indoorRoom.indoorMap.building', 'landuse')
                ->where('token', $request->string('destination_link')->toString())
                ->where('is_active', true)
                ->first();
        }

        return $this->dashboardView($destinationLink, false);
    }

    public function GuestDashboard(?DestinationLink $destinationLink = null)
    {
        return $this->dashboardView($destinationLink, true);
    }

    private function dashboardView(?DestinationLink $destinationLink, bool $guestMode)
    {
        $sharedDestination = $destinationLink ? [
            'type' => $destinationLink->destination_type,
            'id' => $destinationLink->destination_id,
            'label' => $destinationLink->destinationLabel(),
        ] : null;

        return view('user.dashboard', compact('guestMode', 'sharedDestination'));
    }

    public function Userlogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
