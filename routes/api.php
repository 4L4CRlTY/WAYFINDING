<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['wayfinding.client', 'throttle:wayfinding-map', 'cache.wayfinding:600'])->group(function () {
    Route::get('/buildings', [ApiController::class, 'buildings']);
    Route::get('/paths', [ApiController::class, 'paths']);
    Route::get('/entry-points', [ApiController::class, 'entryPoints']);
    Route::get('/building-entrances', [ApiController::class, 'buildingEntrances']);
    Route::get('/hazard-points', [ApiController::class, 'hazardPoints']);
    Route::get('/landuses', [ApiController::class, 'landuses']);

    Route::get('/indoor-maps', [ApiController::class, 'indoorMaps']);
    Route::get('/indoor-rooms', [ApiController::class, 'indoorRooms']);
    Route::get('/indoor-paths', [ApiController::class, 'indoorPaths']);
    Route::get('/indoor-entrances', [ApiController::class, 'indoorEntrances']);
    Route::get('/building-entrance-links', [ApiController::class, 'buildingEntranceLinks']);
    Route::get('/indoor-stairs-links', [ApiController::class, 'indoorStairsLinks']);
});

Route::get('/search-destination', [ApiController::class, 'searchDestination'])
    ->middleware(['wayfinding.client', 'throttle:wayfinding-search']);

Route::get('/campus-events', [ApiController::class, 'campusEvents'])
    ->middleware(['wayfinding.client', 'throttle:wayfinding-map', 'cache.wayfinding:30']);
