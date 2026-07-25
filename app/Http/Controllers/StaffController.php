<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function StaffDashboard(){
        return view("staff.dashboard");
    }
}
