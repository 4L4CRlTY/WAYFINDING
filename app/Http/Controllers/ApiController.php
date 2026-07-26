<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Path;
use App\Models\EntryPoint;
use App\Models\BuildingEntrance;
use App\Models\HazardPoint;
use App\Models\LandUse;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Models\IndoorPath;
use App\Models\IndoorEntrance;
use App\Models\BuildingEntranceLink;
use App\Models\IndoorStairLink;
use App\Models\CampusEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DestinationKeyword;

class ApiController extends Controller
{
    public function buildings()
    {
        return response()->json(
            Building::select('id', 'name', 'geometry', 'properties', 'color')
                ->orderBy('name')
                ->get()
                ->map(function ($building) {
                    return [
                        'id' => $building->id,
                        'name' => $building->name,
                        'geometry' => is_array($building->geometry)
                            ? $building->geometry
                            : json_decode($building->geometry, true),
                        'properties' => is_array($building->properties)
                            ? $building->properties
                            : json_decode($building->properties, true),
                        'color' => $building->color,
                    ];
                })
                ->values()
        );
    }

    public function paths()
    {
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => Path::orderBy('id')->get()->map(function ($path) {
                return [
                    'type' => 'Feature',
                    'geometry' => is_array($path->geometry)
                        ? $path->geometry
                        : json_decode($path->geometry, true),
                    'properties' => [
                        'id' => $path->id,
                        'name' => $path->name,
                        'type' => $path->type,
                        'risk_level' => (int) ($path->risk_level ?? 1),
                        'difficulty_level' => (int) ($path->difficulty_level ?? 1),
                        'is_blocked' => (bool) ($path->is_blocked ?? false),
                        'properties' => is_array($path->properties)
                            ? $path->properties
                            : json_decode($path->properties, true),
                    ],
                ];
            })->values(),
        ]);
    }

    public function entryPoints()
    {
        return response()->json(
            EntryPoint::orderBy('name')->get()->map(function ($point) {
                $geometry = is_array($point->geometry)
                    ? $point->geometry
                    : json_decode($point->geometry, true);

                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'latitude' => $geometry['coordinates'][1] ?? null,
                    'longitude' => $geometry['coordinates'][0] ?? null,
                ];
            })->values()
        );
    }

    public function buildingEntrances()
    {
        return response()->json(
            BuildingEntrance::with('building')
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get()
                ->map(function ($entrance) {
                    return [
                        'id' => $entrance->id,
                        'building_id' => $entrance->building_id,
                        'building_name' => $entrance->building->name ?? null,
                        'name' => $entrance->name,
                        'is_primary' => (bool) $entrance->is_primary,
                        'latitude' => (float) $entrance->latitude,
                        'longitude' => (float) $entrance->longitude,
                    ];
                })->values()
        );
    }

    public function hazardPoints()
    {
        return response()->json(
            HazardPoint::orderBy('id')->get()->map(function ($hazard) {
                return [
                    'id' => $hazard->id,
                    'path_id' => $hazard->path_id,
                    'title' => $hazard->title,
                    'description' => $hazard->description,
                    'warning_type' => $hazard->warning_type,
                    'severity_level' => (int) $hazard->severity_level,
                    'latitude' => (float) $hazard->latitude,
                    'longitude' => (float) $hazard->longitude,
                    'affects_routing' => (bool) $hazard->affects_routing,
                    'is_active' => (bool) $hazard->is_active,
                ];
            })->values()
        );
    }

    public function landuses()
    {
        return response()->json(
            \App\Models\Landuse::orderBy('id')->get()->map(function ($landuse) {
                return [
                    'id' => $landuse->id,
                    'name' => $landuse->name,
                    // type = design / normal landuse category.
                    // User side uses this to keep design landuse display-only
                    // and remove it from routing destination choices.
                    'type' => $landuse->type ?? null,
                    'landuse_type' => $landuse->type ?? null,
                    'geometry' => is_array($landuse->geometry)
                        ? $landuse->geometry
                        : json_decode($landuse->geometry, true),
                    'properties' => is_array($landuse->properties)
                        ? $landuse->properties
                        : json_decode($landuse->properties, true),

                    // basic
                    'image' => $landuse->image,
                    'image_width' => (int) ($landuse->image_width ?? 120),
                    'image_height' => (int) ($landuse->image_height ?? 120),
                    'image_rotation' => (int) ($landuse->image_rotation ?? 0),
                    'image_offset_x' => (int) ($landuse->image_offset_x ?? 0),
                    'image_offset_y' => (int) ($landuse->image_offset_y ?? 0),

                    // normalized
                    'image_scale_x' => (float) ($landuse->image_scale_x ?? 1),
                    'image_scale_y' => (float) ($landuse->image_scale_y ?? 1),
                    'image_offset_x_ratio' => (float) ($landuse->image_offset_x_ratio ?? 0),
                    'image_offset_y_ratio' => (float) ($landuse->image_offset_y_ratio ?? 0),

                    // polygon-aware
                    'polygon_base_angle' => (float) ($landuse->polygon_base_angle ?? 0),
                    'image_local_scale_x' => (float) ($landuse->image_local_scale_x ?? 1),
                    'image_local_scale_y' => (float) ($landuse->image_local_scale_y ?? 1),
                    'image_local_offset_u' => (float) ($landuse->image_local_offset_u ?? 0),
                    'image_local_offset_v' => (float) ($landuse->image_local_offset_v ?? 0),
                    'image_local_rotation' => (float) ($landuse->image_local_rotation ?? 0),

                    // final exact anchors
                    'image_tl_lat' => $landuse->image_tl_lat !== null ? (float) $landuse->image_tl_lat : null,
                    'image_tl_lng' => $landuse->image_tl_lng !== null ? (float) $landuse->image_tl_lng : null,
                    'image_tr_lat' => $landuse->image_tr_lat !== null ? (float) $landuse->image_tr_lat : null,
                    'image_tr_lng' => $landuse->image_tr_lng !== null ? (float) $landuse->image_tr_lng : null,
                    'image_bl_lat' => $landuse->image_bl_lat !== null ? (float) $landuse->image_bl_lat : null,
                    'image_bl_lng' => $landuse->image_bl_lng !== null ? (float) $landuse->image_bl_lng : null,
                    'image_br_lat' => $landuse->image_br_lat !== null ? (float) $landuse->image_br_lat : null,
                    'image_br_lng' => $landuse->image_br_lng !== null ? (float) $landuse->image_br_lng : null,
                ];
            })->values()
        );
    }
    public function indoorMaps()
    {
        return response()->json(
            IndoorMap::with('building')
                ->where('is_active', true)
                ->orderBy('building_id')
                ->orderBy('floor_number')
                ->get()
                ->map(function ($map) {
                    return [
                        'id' => $map->id,
                        'building_id' => $map->building_id,
                        'building_name' => $map->building->name ?? null,
                        'name' => $map->name,
                        'floor_number' => (int) $map->floor_number,
                        'floor_label' => $map->floor_label ?: (((int) $map->floor_number === 0) ? '0F / Basement' : ((int) $map->floor_number . 'F')),
                        'floorplan_image' => $map->floorplan_image
                            ? asset('floorplan_image/' . $map->floorplan_image)
                            : null,
                        'backup_floorplan_image' => $map->backup_floorplan_image
                            ? asset('floorplan_image/' . $map->backup_floorplan_image)
                            : null,
                        'width' => $map->width ? (int) $map->width : null,
                        'height' => $map->height ? (int) $map->height : null,
                        'geometry' => is_array($map->geometry)
                            ? $map->geometry
                            : json_decode($map->geometry, true),
                        'is_active' => (bool) $map->is_active,
                    ];
                })->values()
        );
    }

    public function indoorRooms()
    {
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => IndoorRoom::with('indoorMap.building')
                ->orderBy('id')
                ->get()
                ->map(function ($room) {
                    return [
                        'type' => 'Feature',
                        'geometry' => is_array($room->geometry)
                            ? $room->geometry
                            : json_decode($room->geometry, true),
                        'properties' => [
                            'id' => $room->id,
                            'indoor_map_id' => $room->indoor_map_id,
                            'building_id' => $room->indoorMap->building_id ?? null,
                            'building_name' => $room->indoorMap->building->name ?? null,
                            'floor_number' => $room->indoorMap->floor_number ?? null,
                            'floor_label' => $room->indoorMap ? ($room->indoorMap->floor_label ?: (((int) $room->indoorMap->floor_number === 0) ? '0F / Basement' : ((int) $room->indoorMap->floor_number . 'F'))) : null,
                            'name' => $room->name,
                            'room_code' => $room->room_code,
                            'type' => $room->type,
                            'properties' => is_array($room->properties)
                                ? $room->properties
                                : json_decode($room->properties, true),
                        ],
                    ];
                })->values(),
        ]);
    }

    public function indoorPaths()
    {
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => IndoorPath::with('indoorMap.building')
                ->orderBy('id')
                ->get()
                ->map(function ($path) {
                    return [
                        'type' => 'Feature',
                        'geometry' => is_array($path->geometry)
                            ? $path->geometry
                            : json_decode($path->geometry, true),
                        'properties' => [
                            'id' => $path->id,
                            'indoor_map_id' => $path->indoor_map_id,
                            'building_id' => $path->indoorMap->building_id ?? null,
                            'building_name' => $path->indoorMap->building->name ?? null,
                            'floor_number' => $path->indoorMap->floor_number ?? null,
                            'floor_label' => $path->indoorMap ? ($path->indoorMap->floor_label ?: (((int) $path->indoorMap->floor_number === 0) ? '0F / Basement' : ((int) $path->indoorMap->floor_number . 'F'))) : null,
                            'name' => $path->name,
                            'path_type' => $path->path_type,
                            'is_blocked' => (bool) $path->is_blocked,
                            'properties' => is_array($path->properties)
                                ? $path->properties
                                : json_decode($path->properties, true),
                        ],
                    ];
                })->values(),
        ]);
    }

    public function indoorEntrances()
    {
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => IndoorEntrance::with('indoorMap.building')
                ->orderBy('id')
                ->get()
                ->map(function ($entrance) {
                    return [
                        'type' => 'Feature',
                        'geometry' => is_array($entrance->geometry)
                            ? $entrance->geometry
                            : json_decode($entrance->geometry, true),
                        'properties' => [
                            'id' => $entrance->id,
                            'indoor_map_id' => $entrance->indoor_map_id,
                            'building_id' => $entrance->indoorMap->building_id ?? null,
                            'building_name' => $entrance->indoorMap->building->name ?? null,
                            'floor_number' => $entrance->indoorMap->floor_number ?? null,
                            'floor_label' => $entrance->indoorMap ? ($entrance->indoorMap->floor_label ?: (((int) $entrance->indoorMap->floor_number === 0) ? '0F / Basement' : ((int) $entrance->indoorMap->floor_number . 'F'))) : null,
                            'name' => $entrance->name,
                            'ent_type' => $entrance->ent_type,
                            'room_code' => $entrance->room_code,
                            'properties' => is_array($entrance->properties)
                                ? $entrance->properties
                                : json_decode($entrance->properties, true),
                        ],
                    ];
                })->values(),
        ]);
    }

    public function buildingEntranceLinks()
    {
        return response()->json(
            BuildingEntranceLink::with([
                'building',
                'buildingEntrance',
                'indoorEntrance.indoorMap',
            ])
                ->orderBy('id')
                ->get()
                ->map(function ($link) {
                    $indoorGeometry = is_array($link->indoorEntrance?->geometry)
                        ? $link->indoorEntrance?->geometry
                        : json_decode($link->indoorEntrance?->geometry ?? 'null', true);

                    return [
                        'id' => $link->id,
                        'building_id' => $link->building_id,
                        'building_name' => $link->building->name ?? null,
                        'name' => $link->name,
                        'building_entrance_id' => $link->building_entrance_id,
                        'indoor_entrance_id' => $link->indoor_entrance_id,

                        'outdoor_entrance' => [
                            'id' => $link->buildingEntrance?->id,
                            'name' => $link->buildingEntrance?->name,
                            'is_primary' => (bool) ($link->buildingEntrance?->is_primary ?? false),
                            'latitude' => (float) ($link->buildingEntrance?->latitude ?? 0),
                            'longitude' => (float) ($link->buildingEntrance?->longitude ?? 0),
                        ],

                        'indoor_entrance' => [
                            'id' => $link->indoorEntrance?->id,
                            'name' => $link->indoorEntrance?->name,
                            'ent_type' => $link->indoorEntrance?->ent_type,
                            'room_code' => $link->indoorEntrance?->room_code,
                            'floor_number' => $link->indoorEntrance?->indoorMap?->floor_number,
                            'floor_label' => $link->indoorEntrance?->indoorMap ? ($link->indoorEntrance->indoorMap->floor_label ?: (((int) $link->indoorEntrance->indoorMap->floor_number === 0) ? '0F / Basement' : ((int) $link->indoorEntrance->indoorMap->floor_number . 'F'))) : null,
                            'geometry' => $indoorGeometry,
                        ],
                    ];
                })->values()
        );
    }


    public function searchDestination(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if ($query === '') {
            return response()->json([
                'success' => false,
                'is_keyword_match' => false,
                'source' => 'destination_keywords',
                'message' => 'Search text is required.'
            ], 422);
        }

        $normalizeText = function ($text) {
            $text = mb_strtolower((string) $text);

            $phrasesToRemove = [
                'i want to go to',
                'i wanna go to',
                'i need to go to',
                'take me to',
                'route me to',
                'bring me to',
                'navigate to',
                'go to',
                'where is',
                'find',
                'search',
                'please',
                'room',
                'office',

                // Bisaya / Cebuano
                'asa ang',
                'asa dapit ang',
                'adto ko sa',
                'ganahan ko moadto sa',
                'moadto ko sa',
                'dad-a ko sa',
                'pangitaa ang',
                'pangita ang',
                'palihog',
                'kwarto',
                'opisina',
            ];

            foreach ($phrasesToRemove as $phrase) {
                $text = str_replace($phrase, ' ', $text);
            }

            $text = preg_replace('/[^a-z0-9\s]/iu', ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);

            return trim($text);
        };

        $normalized = $normalizeText($query);

        if ($normalized === '') {
            return response()->json([
                'success' => false,
                'is_keyword_match' => false,
                'source' => 'destination_keywords',
                'message' => 'No searchable keyword found in the text.'
            ], 422);
        }

        $queryWords = array_values(array_filter(explode(' ', $normalized)));

        /*
        |--------------------------------------------------------------------------
        | STRICT RULE
        |--------------------------------------------------------------------------
        | Search / voice routing is only allowed if the text matches an active row
        | from destination_keywords. No direct fallback to buildings or rooms.
        |--------------------------------------------------------------------------
        */
        $keywords = DestinationKeyword::where('is_active', true)
            ->orderByDesc('priority')
            ->orderByRaw('LENGTH(keyword) DESC')
            ->get();

        if ($keywords->isEmpty()) {
            return response()->json([
                'success' => false,
                'is_keyword_match' => false,
                'source' => 'destination_keywords',
                'message' => 'No active destination keywords found. Please add keywords in admin first.'
            ], 404);
        }

        $scoreKeyword = function ($keywordText) use ($normalizeText, $normalized, $queryWords) {
            $dbKeyword = $normalizeText($keywordText ?? '');

            if ($dbKeyword === '') {
                return -1;
            }

            $score = -1;

            if ($normalized === $dbKeyword) {
                $score = 2000 + mb_strlen($dbKeyword);
            } elseif (preg_match('/(^|\s)' . preg_quote($dbKeyword, '/') . '(\s|$)/u', $normalized)) {
                $score = 1700 + mb_strlen($dbKeyword);
            } elseif (str_contains($normalized, $dbKeyword)) {
                $score = 1500 + mb_strlen($dbKeyword);
            } else {
                $keywordWords = array_values(array_filter(explode(' ', $dbKeyword)));
                $common = array_intersect($queryWords, $keywordWords);

                if (!empty($common)) {
                    $score = (count($common) * 120) + mb_strlen($dbKeyword);
                }
            }

            return $score;
        };

        $matchedKeywords = [];

        foreach ($keywords as $keyword) {
            $score = $scoreKeyword($keyword->keyword);

            if ($score < 100) {
                continue;
            }

            if ($keyword->destination_type === 'room') {
                $score += 350;
            }

            if ($keyword->destination_type === 'building') {
                $score += 120;
            }

            $matchedKeywords[] = [
                'model' => $keyword,
                'score' => $score,
                'keyword' => $keyword->keyword,
                'type' => $keyword->destination_type,
                'destination_id' => (int) $keyword->destination_id,
            ];
        }

        if (empty($matchedKeywords)) {
            return response()->json([
                'success' => false,
                'is_keyword_match' => false,
                'source' => 'destination_keywords',
                'message' => 'No active destination keyword matched your text. Please ask admin to add this keyword first.'
            ], 404);
        }

        usort($matchedKeywords, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        /*
        |--------------------------------------------------------------------------
        | Detect building context from keyword table only.
        |--------------------------------------------------------------------------
        | Example:
        | "B3 room 101"
        | - B3 must be an active building keyword.
        | - room 101 must be an active room keyword.
        | - final room must belong to B3.
        |--------------------------------------------------------------------------
        */
        $buildingMatches = array_values(array_filter($matchedKeywords, function ($match) {
            return $match['type'] === 'building';
        }));

        $detectedBuilding = null;
        $detectedBuildingKeyword = null;

        if (!empty($buildingMatches)) {
            $detectedBuilding = Building::find($buildingMatches[0]['destination_id']);
            $detectedBuildingKeyword = $buildingMatches[0];
        }

        /*
        | An exact building-only query must not be captured by room codes that
        | reuse the building acronym (for example IT versus IT-101).
        */
        if (
            $detectedBuilding
            && $normalized === $normalizeText($detectedBuildingKeyword['keyword'] ?? '')
        ) {
            return response()->json([
                'success' => true,
                'is_keyword_match' => true,
                'source' => 'destination_keywords',
                'match_type' => 'building',
                'matched_keyword' => $detectedBuildingKeyword['keyword'],
                'matched_keywords' => [$detectedBuildingKeyword['keyword']],
                'matched_keyword_ids' => [$detectedBuildingKeyword['model']->id ?? null],
                'result' => [
                    'destination_type' => 'building',
                    'destination_id' => $detectedBuilding->id,
                    'label' => $detectedBuilding->name ?: 'Building',
                ],
            ]);
        }

        /*
        |-------------------------------------------------------------------------- 
        | Room keyword has highest priority, but building context filters it.
        |--------------------------------------------------------------------------
        */
        $roomMatches = array_values(array_filter($matchedKeywords, function ($match) {
            return $match['type'] === 'room';
        }));

        if (!empty($roomMatches)) {
            $bestRoom = null;
            $bestRoomMatch = null;

            foreach ($roomMatches as $roomMatch) {
                $room = IndoorRoom::with('indoorMap.building')->find($roomMatch['destination_id']);

                if (!$room || !$room->indoorMap) {
                    continue;
                }

                if ($detectedBuilding && (int) $room->indoorMap->building_id !== (int) $detectedBuilding->id) {
                    continue;
                }

                $bestRoom = $room;
                $bestRoomMatch = $roomMatch;
                break;
            }

            if (!$bestRoom && $detectedBuilding) {
                return response()->json([
                    'success' => false,
                    'is_keyword_match' => false,
                    'source' => 'destination_keywords',
                    'message' => 'A room keyword matched, but it is not under the detected building keyword.'
                ], 404);
            }

            if ($bestRoom) {
                return response()->json([
                    'success' => true,
                    'is_keyword_match' => true,
                    'source' => 'destination_keywords',
                    'match_type' => 'room',
                    'matched_keyword' => $bestRoomMatch['keyword'],
                    'matched_keywords' => array_values(array_filter([
                        $detectedBuilding->name ?? null,
                        $detectedBuildingKeyword['keyword'] ?? null,
                        $bestRoomMatch['keyword'],
                        $bestRoom->room_code,
                        $bestRoom->name,
                    ])),
                    'matched_keyword_ids' => array_values(array_filter([
                        $detectedBuildingKeyword['model']->id ?? null,
                        $bestRoomMatch['model']->id ?? null,
                    ])),
                    'result' => [
                        'destination_type' => 'room',
                        'destination_id' => $bestRoom->id,
                        'label' => $bestRoom->name ?: ($bestRoom->room_code ?: 'Room / Office'),
                        'room_code' => $bestRoom->room_code,
                        'building_id' => $bestRoom->indoorMap->building_id ?? null,
                        'building_name' => $bestRoom->indoorMap->building->name ?? null,
                        'floor_number' => $bestRoom->indoorMap->floor_number ?? null,
                        'floor_label' => $bestRoom->indoorMap->floor_label ?? null,
                    ]
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Building keyword route.
        |--------------------------------------------------------------------------
        */
        if ($detectedBuilding) {
            return response()->json([
                'success' => true,
                'is_keyword_match' => true,
                'source' => 'destination_keywords',
                'match_type' => 'building',
                'matched_keyword' => $detectedBuildingKeyword['keyword'],
                'matched_keywords' => [$detectedBuildingKeyword['keyword']],
                'matched_keyword_ids' => [$detectedBuildingKeyword['model']->id ?? null],
                'result' => [
                    'destination_type' => 'building',
                    'destination_id' => $detectedBuilding->id,
                    'label' => $detectedBuilding->name ?: 'Building',
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Landuse keyword route.
        |--------------------------------------------------------------------------
        */
        $landuseMatches = array_values(array_filter($matchedKeywords, function ($match) {
            return $match['type'] === 'landuse';
        }));

        if (!empty($landuseMatches)) {
            $landuse = \App\Models\Landuse::find($landuseMatches[0]['destination_id']);

            if ($landuse) {
                return response()->json([
                    'success' => true,
                    'is_keyword_match' => true,
                    'source' => 'destination_keywords',
                    'match_type' => 'landuse',
                    'matched_keyword' => $landuseMatches[0]['keyword'],
                    'matched_keywords' => [$landuseMatches[0]['keyword']],
                    'matched_keyword_ids' => [$landuseMatches[0]['model']->id ?? null],
                    'result' => [
                        'destination_type' => 'landuse',
                        'destination_id' => $landuse->id,
                        'label' => $landuse->name ?: 'Landuse Area',
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'is_keyword_match' => false,
            'source' => 'destination_keywords',
            'message' => 'Matched keyword points to a missing destination record.'
        ], 404);
    }
    public function indoorStairsLinks()
    {
        return response()->json(
            IndoorStairLink::with([
                'building',
                'fromEntrance.indoorMap',
                'toEntrance.indoorMap',
            ])
                ->orderBy('id')
                ->get()
                ->map(function ($link) {
                    $fromGeometry = is_array($link->fromEntrance?->geometry)
                        ? $link->fromEntrance?->geometry
                        : json_decode($link->fromEntrance?->geometry ?? 'null', true);

                    $toGeometry = is_array($link->toEntrance?->geometry)
                        ? $link->toEntrance?->geometry
                        : json_decode($link->toEntrance?->geometry ?? 'null', true);

                    return [
                        'id' => $link->id,
                        'building_id' => $link->building_id,
                        'building_name' => $link->building->name ?? null,
                        'name' => $link->name,

                        'from_entrance' => [
                            'id' => $link->fromEntrance?->id,
                            'name' => $link->fromEntrance?->name,
                            'floor_number' => $link->fromEntrance?->indoorMap?->floor_number,
                            'floor_label' => $link->fromEntrance?->indoorMap?->floor_label,
                            'geometry' => $fromGeometry,
                        ],

                        'to_entrance' => [
                            'id' => $link->toEntrance?->id,
                            'name' => $link->toEntrance?->name,
                            'floor_number' => $link->toEntrance?->indoorMap?->floor_number,
                            'floor_label' => $link->toEntrance?->indoorMap?->floor_label,
                            'geometry' => $toGeometry,
                        ],
                    ];
                })->values()
        );
    }

    public function campusEvents()
    {
        $now = Carbon::now();

        $events = CampusEvent::with([
            'building',
            'indoorRoom.indoorMap.building',
            'landuse',
        ])
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where(function ($current) use ($now) {
                    $current->where('starts_at', '<=', $now)
                        ->where(function ($end) use ($now) {
                            $end->whereNull('ends_at')
                                ->orWhere('ends_at', '>=', $now);
                        });
                })
                    ->orWhere('starts_at', '>', $now);
            })
            ->orderByDesc('priority')
            ->orderBy('starts_at')
            ->get();

        return response()->json(
            $events->map(function ($event) use ($now) {
                $targetType = $event->event_target_type;

                $displayType = null;
                $displayId = null;

                $routeType = null;
                $routeId = null;

                $buildingId = null;
                $buildingName = null;
                $roomId = null;
                $roomName = null;
                $roomCode = null;
                $floorLabel = null;
                $landuseId = null;
                $landuseName = null;

                if ($targetType === 'building') {
                    $displayType = 'building';
                    $displayId = $event->building_id;

                    $routeType = 'building';
                    $routeId = $event->building_id;

                    $buildingId = $event->building_id;
                    $buildingName = optional($event->building)->name;
                }

                if ($targetType === 'room') {
                    $room = $event->indoorRoom;
                    $indoorMap = optional($room)->indoorMap;
                    $building = optional($indoorMap)->building;

                    $displayType = 'building';
                    $displayId = optional($building)->id;

                    $routeType = 'room';
                    $routeId = optional($room)->id;

                    $buildingId = optional($building)->id;
                    $buildingName = optional($building)->name;

                    $roomId = optional($room)->id;
                    $roomName = optional($room)->name;
                    $roomCode = optional($room)->room_code;
                    $floorLabel = optional($indoorMap)->floor_label;
                }

                if ($targetType === 'landuse') {
                    $displayType = 'landuse';
                    $displayId = $event->landuse_id;

                    $routeType = 'landuse';
                    $routeId = $event->landuse_id;

                    $landuseId = $event->landuse_id;
                    $landuseName = optional($event->landuse)->name;
                }

                $status = 'upcoming';

                if ($event->starts_at && $event->starts_at->lte($now)) {
                    if (!$event->ends_at || $event->ends_at->gte($now)) {
                        $status = 'happening_now';
                    }
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'event_target_type' => $targetType,

                    'display_type' => $displayType,
                    'display_id' => $displayId,

                    'route_type' => $routeType,
                    'route_id' => $routeId,

                    'building_id' => $buildingId,
                    'building_name' => $buildingName,

                    'room_id' => $roomId,
                    'room_name' => $roomName,
                    'room_code' => $roomCode,
                    'floor_label' => $floorLabel,

                    'landuse_id' => $landuseId,
                    'landuse_name' => $landuseName,

                    'location_label' => $event->location_label,
                    'starts_at' => optional($event->starts_at)->toDateTimeString(),
                    'ends_at' => optional($event->ends_at)->toDateTimeString(),

                    'starts_at_display' => optional($event->starts_at)->format('M d, Y h:i A'),
                    'ends_at_display' => $event->ends_at
                        ? optional($event->ends_at)->format('M d, Y h:i A')
                        : null,

                    'status' => $status,
                    'priority' => (int) $event->priority,
                ];
            })->values()
        );
    }
}
