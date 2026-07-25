<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| OUTDOOR ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('')->group(function () {
    Route::get('/buildings', [ApiController::class, 'buildings']);
    Route::get('/paths', [ApiController::class, 'paths']);
    Route::get('/entry-points', [ApiController::class, 'entryPoints']);
    Route::get('/building-entrances', [ApiController::class, 'buildingEntrances']);
    Route::get('/hazard-points', [ApiController::class, 'hazardPoints']);
    Route::get('/landuses', [ApiController::class, 'landuses']);
});

/*
|--------------------------------------------------------------------------
| INDOOR ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('')->group(function () {
    Route::get('/indoor-maps', [ApiController::class, 'indoorMaps']);
    Route::get('/indoor-rooms', [ApiController::class, 'indoorRooms']);
    Route::get('/indoor-paths', [ApiController::class, 'indoorPaths']);
    Route::get('/indoor-entrances', [ApiController::class, 'indoorEntrances']);
    Route::get('/building-entrance-links', [ApiController::class, 'buildingEntranceLinks']);
    Route::get('/indoor-stairs-links', [ApiController::class, 'indoorStairsLinks']);
});

/*
|--------------------------------------------------------------------------
| TEXT TO DESTINATION
|--------------------------------------------------------------------------
*/
Route::get('/search-destination', [ApiController::class, 'searchDestination']);
/*
|--------------------------------------------------------------------------
| Campus Events
|--------------------------------------------------------------------------
*/
Route::get('/campus-events', [ApiController::class, 'campusEvents']);
