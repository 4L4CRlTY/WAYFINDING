<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingEntranceController;
use App\Http\Controllers\BuildingEntranceLinkController;
use App\Http\Controllers\CampusEventController;
use App\Http\Controllers\DestinationKeywordController;
use App\Http\Controllers\EntryPointController;
use App\Http\Controllers\HazardPointController;
use App\Http\Controllers\IndoorEntranceController;
use App\Http\Controllers\IndoorMapController;
use App\Http\Controllers\IndoorPathController;
use App\Http\Controllers\IndoorRoomController;
use App\Http\Controllers\IndoorStairsLink;
use App\Http\Controllers\LandUseController;
use App\Http\Controllers\PathController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';


//ADMIN GROUP MIDDLEWARE
Route::middleware(['auth', 'roles:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'AdminDashboard'])->name('admin.dashboard');
    Route::get('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');

    //BUILDINGS
    Route::get('/admin/buildings', [BuildingController::class, 'Buildings'])->name('admin.buildings');
    Route::post('/admin/buildings/upload', [BuildingController::class, 'uploadBuildings'])->name('admin.buildings.upload');
    Route::delete('/admin/buildings/reset', [BuildingController::class, 'resetBuildings'])->name('admin.buildings.reset');
    Route::patch('/admin/buildings/{building}/update-name', [BuildingController::class, 'updateName'])->name('admin.buildings.updateName');
    Route::patch('/admin/buildings/{building}/update-color', [BuildingController::class, 'updateColor'])->name('admin.buildings.updateColor');


    //PATHS
    Route::get('/admin/path', [PathController::class, 'Path'])->name('admin.path');
    Route::post('/admin/path/upload', [PathController::class, 'uploadPath'])->name('admin.path.upload');
    Route::delete('/admin/path/reset', [PathController::class, 'resetPath'])->name('admin.path.reset');
    Route::patch('/admin/path/{path}/update-name', [PathController::class, 'updateName'])->name('admin.path.updateName');
    Route::patch('/admin/path/{path}/update-settings', [PathController::class, 'updateSettings'])->name('admin.path.updateSettings');


    //HAZARD POINTS
    Route::get('/admin/hazard-point', [HazardPointController::class, 'HazardPoint'])->name('admin.hazard-point');
    Route::post('/admin/hazard-point/store', [HazardPointController::class, 'store'])->name('admin.hazard-point.store');
    Route::patch('/admin/hazard-point/{hazardPoint}/update', [HazardPointController::class, 'update'])->name('admin.hazard-point.update');
    Route::delete('/admin/hazard-point/{hazardPoint}', [HazardPointController::class, 'destroy'])->name('admin.hazard-point.destroy');

    //Entry Point
    Route::get('/admin/entry-point', [EntryPointController::class, 'EntryPoint'])->name('admin.entry-point');
    Route::post('/admin/entry-point/upload', [EntryPointController::class, 'uploadEntryPoint'])->name('admin.entry-point.upload');
    Route::delete('/admin/entry-point/reset', [EntryPointController::class, 'resetEntryPoint'])->name('admin.entry-point.reset');
    Route::patch('/admin/entry-point/{entry_point}/update-name', [EntryPointController::class, 'updateName'])->name('admin.entry-point.updateName');

    //LandUse
    Route::get('/admin/landuse', [LandUseController::class, 'LandUse'])->name('admin.landuse');
    Route::post('/admin/landuse/upload', [LandUseController::class, 'uploadLandUse'])->name('admin.landuse.upload');
    Route::delete('/admin/landuse/reset', [LandUseController::class, 'resetLandUse'])->name('admin.landuse.reset');
    Route::patch('/admin/landuse/{landuse}/update-name', [LandUseController::class, 'updateName'])->name('admin.landuse.updateName');
    Route::post('/admin/landuse/{landuse}/update-image', [LandUseController::class, 'updateImage'])->name('admin.landuse.updateImage');
    Route::post('/admin/landuse/{landuse}/update-editor', [LandUseController::class, 'updateEditor'])->name('admin.landuse.updateEditor');

    //BUILDING ENTRANCE
    Route::get('/admin/building-entrances', [BuildingEntranceController::class, 'BuildingEntrance'])->name('admin.building-entrances');
    Route::post('/admin/building-entrances/store', [BuildingEntranceController::class, 'store'])->name('admin.building-entrances.store');
    Route::put('/admin/building-entrances/{buildingEntrance}', [BuildingEntranceController::class, 'update'])->name('admin.building-entrances.update');
    Route::delete('/admin/building-entrances/{buildingEntrance}', [BuildingEntranceController::class, 'destroy'])->name('admin.building-entrances.destroy');



    //INDOOR MAPS
    Route::get('/admin/indoor-map', [IndoorMapController::class, 'IndoorMap'])->name('admin.indoor-map');
    Route::post('/admin/indoor-map/upload', [IndoorMapController::class, 'uploadIndoorMap'])->name('admin.indoor-map.upload');
    Route::delete('/admin/indoor-map/reset', [IndoorMapController::class, 'resetIndoorMap'])->name('admin.indoor-map.reset');
    Route::post('/admin/indoor-map/{map}/update', [IndoorMapController::class, 'updateIndoorMap'])->name('admin.indoor-map.update');

    //INDOOR ROOMS
    Route::get('/admin/indoor-room', [IndoorRoomController::class, 'IndoorRoom'])->name('admin.indoor-room');
    Route::post('/admin/indoor-room/upload', [IndoorRoomController::class, 'uploadIndoorRooms'])->name('admin.indoor-room.upload');
    Route::delete('/admin/indoor-room/reset', [IndoorRoomController::class, 'resetIndoorRooms'])->name('admin.indoor-room.reset');
    Route::post('/admin/indoor-room/{room}/update', [IndoorRoomController::class, 'updateIndoorRoom'])->name('admin.indoor-room.update');

    //INDOOR PATHS
    Route::get('/admin/indoor-path', [IndoorPathController::class, 'IndoorPath'])->name('admin.indoor-path');
    Route::post('/admin/indoor-path/upload', [IndoorPathController::class, 'uploadIndoorPaths'])->name('admin.indoor-path.upload');
    Route::post('/admin/indoor-path/{path}/update', [IndoorPathController::class, 'updateIndoorPath'])->name('admin.indoor-path.update');
    Route::delete('/admin/indoor-path/delete-building', [IndoorPathController::class, 'deleteByBuilding'])->name('admin.indoor-path.delete-building');
    Route::delete('/admin/indoor-path/{path}/delete', [IndoorPathController::class, 'deleteIndoorPath'])->name('admin.indoor-path.delete');

    //INDOOR ENTRANCES
    Route::get('/admin/indoor-entrances', [IndoorEntranceController::class, 'IndoorEntrances'])->name('admin.indoor-entrances');
    Route::post('/admin/indoor-entrances/upload', [IndoorEntranceController::class, 'uploadIndoorEntrances'])->name('admin.indoor-entrances.upload');
    Route::post('/admin/indoor-entrances/{entrance}/update', [IndoorEntranceController::class, 'updateIndoorEntrance'])->name('admin.indoor-entrances.update');
    Route::delete('/admin/indoor-entrances/delete-building', [IndoorEntranceController::class, 'deleteByBuilding'])->name('admin.indoor-entrances.delete-building');
    Route::delete('/admin/indoor-entrances/{entrance}/delete', [IndoorEntranceController::class, 'deleteIndoorEntrance'])->name('admin.indoor-entrances.delete');

    //INDOOR STAIRSLINK
    Route::get('/admin/indoor-stairs-link', [IndoorStairsLink::class, 'IndoorStairsLink'])->name('admin.indoor-stairs-link');
    Route::post('/admin/indoor-stairs-link/store', [IndoorStairsLink::class, 'store'])->name('admin.indoor-stairs-link.store');
    Route::post('/admin/indoor-stairs-link/{link}/update', [IndoorStairsLink::class, 'update'])->name('admin.indoor-stairs-link.update');
    Route::delete('/admin/indoor-stairs-link/{link}/delete', [IndoorStairsLink::class, 'destroy'])->name('admin.indoor-stairs-link.delete');

    //Building ENTRANCE LINKS
    Route::get('/admin/building-entrance-link', [BuildingEntranceLinkController::class, 'BuildingEntranceLink'])->name('admin.building-entrance-link');
    Route::post('/admin/building-entrance-link/store', [BuildingEntranceLinkController::class, 'store'])->name('admin.building-entrance-link.store');
    Route::post('/admin/building-entrance-link/{link}/update', [BuildingEntranceLinkController::class, 'update'])->name('admin.building-entrance-link.update');
    Route::delete('/admin/building-entrance-link/{link}/delete', [BuildingEntranceLinkController::class, 'destroy'])->name('admin.building-entrance-link.delete');

    //DESTINATION KEYWORD
    Route::get('/admin/destination-keyword', [DestinationKeywordController::class, 'DestinationKeyword'])->name('admin.destination-keyword');
    Route::post('/admin/destination-keyword/store', [DestinationKeywordController::class, 'store'])->name('admin.destination-keyword.store');
    Route::delete('/admin/destination-keyword/{destinationKeyword}', [DestinationKeywordController::class, 'destroy'])->name('admin.destination-keyword.destroy');

    //CAMPUS EVENT
    Route::get('/admin/campus-event', [CampusEventController::class, 'CampusEvent'])->name('admin.campus-event');
    Route::post('/admin/campus-event/store', [CampusEventController::class, 'store'])->name('admin.campus-event.store');
    Route::post('/admin/campus-event/{campusEvent}/toggle-status', [CampusEventController::class, 'toggleStatus'])->name('admin.campus-event.toggle-status');
    Route::delete('/admin/campus-event/{campusEvent}/delete', [CampusEventController::class, 'destroy'])->name('admin.campus-event.destroy');
});

Route::middleware(['auth', 'roles:staff'])->group(function () {
    Route::get('/staff/dashboard', [StaffController::class, 'StaffDashboard'])->name('staff.dashboard');
});


Route::middleware(['auth', 'roles:user'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'UserDashboard'])->name('user.dashboard');

    Route::post('/user/logout', [UserController::class, 'Userlogout'])->name('user.logout');
});
