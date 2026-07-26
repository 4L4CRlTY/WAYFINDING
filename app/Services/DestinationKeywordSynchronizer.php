<?php

namespace App\Services;

use App\Models\Building;
use App\Models\DestinationKeyword;
use App\Models\IndoorRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DestinationKeywordSynchronizer
{
    /**
     * Add missing aliases without deleting or changing administrator-created rows.
     *
     * @return array{buildings:int, rooms:int, created:int, existing:int}
     */
    public function sync(): array
    {
        $buildings = Building::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        $rooms = IndoorRoom::query()
            ->with('indoorMap.building:id,name')
            ->orderBy('id')
            ->get();

        $roomCandidates = $rooms->mapWithKeys(
            fn (IndoorRoom $room) => [$room->id => $this->roomCandidates($room)]
        );

        $globalRoomUsage = $this->aliasUsage($rooms, $roomCandidates);
        $buildingRoomUsage = $this->aliasUsage($rooms, $roomCandidates, true);
        $floorRoomUsage = $this->aliasUsage($rooms, $roomCandidates, true, true);

        $created = 0;
        $existing = 0;

        DB::transaction(function () use (
            $buildings,
            $rooms,
            $roomCandidates,
            $globalRoomUsage,
            $buildingRoomUsage,
            $floorRoomUsage,
            &$created,
            &$existing
        ): void {
            foreach ($buildings as $building) {
                foreach ($this->buildingCandidates($building->name) as $candidate) {
                    $this->persist(
                        'building',
                        (int) $building->id,
                        $candidate,
                        $created,
                        $existing
                    );
                }
            }

            foreach ($rooms as $room) {
                $building = $room->indoorMap?->building;

                if (! $building) {
                    continue;
                }

                $buildingKey = (string) $building->id;
                $floorKey = $buildingKey.'|'.$this->floorIdentity($room);
                $qualifiers = $this->buildingQualifiers($building->name);

                foreach ($roomCandidates->get($room->id, []) as $normalized => $candidate) {
                    if (count($globalRoomUsage[$normalized] ?? []) === 1) {
                        $this->persist('room', (int) $room->id, $candidate, $created, $existing);
                    }

                    if (count($buildingRoomUsage[$buildingKey][$normalized] ?? []) === 1) {
                        foreach ($qualifiers as $qualifier) {
                            $this->persist('room', (int) $room->id, [
                                'keyword' => $qualifier.' '.$candidate['keyword'],
                                'priority' => $candidate['priority'],
                            ], $created, $existing);
                        }

                        continue;
                    }

                    if (
                        $room->indoorMap?->floor_label
                        && count($floorRoomUsage[$floorKey][$normalized] ?? []) === 1
                    ) {
                        foreach (array_slice($qualifiers, 0, 2) as $qualifier) {
                            $this->persist('room', (int) $room->id, [
                                'keyword' => $qualifier.' '.$room->indoorMap->floor_label.' '.$candidate['keyword'],
                                'priority' => $candidate['priority'],
                            ], $created, $existing);
                        }
                    }
                }
            }
        });

        return [
            'buildings' => $buildings->count(),
            'rooms' => $rooms->count(),
            'created' => $created,
            'existing' => $existing,
        ];
    }

    /**
     * @return array<string, array{keyword:string, priority:int}>
     */
    public function buildingCandidates(?string $name): array
    {
        $name = $this->clean($name);

        if ($name === '') {
            return [];
        }

        $aliases = [];
        $this->add($aliases, $name, 3);
        $this->addAmpersandVariants($aliases, $name, 2);

        $baseName = preg_replace('/\s+(building|hall)$/iu', '', $name) ?: $name;
        if ($this->normalize($baseName) !== $this->normalize($name)) {
            $this->add($aliases, $baseName, 3);
        }

        $acronym = $this->acronym($name);
        if (mb_strlen($acronym) >= 2) {
            $this->add($aliases, $acronym, 3);
        }

        $normalized = $this->normalize($name);

        $rules = [
            'admin building' => ['Admin', 'Administration', 'Campus Administration'],
            'education building' => ['Education', 'Teacher Education'],
            'covered court' => ['Court', 'Gym', 'Gymnasium'],
            'academic building' => ['Academic'],
            'laboratory high school' => ['LHS', 'Lab High', 'High School'],
            'b8' => ['Building 8'],
            'multipurpose building' => ['MPB', 'Multi Purpose', 'Function Hall'],
            'business administration building' => ['BAB', 'Business Admin', 'BA Building'],
            'library building' => ['Library'],
            'guard house 2' => ['Guardhouse 2', 'Security Post 2'],
            'student center' => ['SC', 'Student Hub'],
            'graduate school building' => ['Graduate School', 'Grad School', 'GS Building'],
            'boarding house' => ['Boarding'],
            'human kinetic building' => ['HKB', 'Human Kinetics', 'Sports Building', 'Physical Education'],
            'male dormitory' => ['Male Dorm', 'Boys Dormitory'],
            'guard house 1' => ['Guardhouse 1', 'Security Post 1'],
            'female dorm 2' => ['Female Dormitory 2', 'Girls Dorm 2'],
            'presidential cottage' => ['President Cottage'],
            'female dorm 1' => ['Female Dormitory 1', 'Girls Dorm 1'],
            'garage' => ['Campus Garage'],
            'science and math building' => ['SMB', 'Science and Math', 'Science Building', 'Math Building'],
            'information technology' => ['IT', 'ICT', 'Computer', 'Computer Building', 'Computing', 'Technology Building'],
            'business incubation building' => ['BIB', 'Business Incubation', 'Incubation Building'],
            'accreditation and social science' => ['A&SS', 'Accreditation', 'Social Science'],
        ];

        foreach ($rules[$normalized] ?? [] as $alias) {
            $this->add($aliases, $alias, 2);
        }

        return $aliases;
    }

    /**
     * @return array<string, array{keyword:string, priority:int}>
     */
    private function roomCandidates(IndoorRoom $room): array
    {
        $aliases = [];
        $name = $this->clean($room->name);
        $code = mb_strtoupper($this->clean($room->room_code));

        $this->add($aliases, $name, 3);
        $this->add($aliases, $code, 3);
        $this->addAmpersandVariants($aliases, $name, 2);

        if ($code !== '') {
            $this->add($aliases, str_replace(['-', '_'], ' ', $code), 2);
        }

        if ($name !== '' && $code !== '') {
            $this->add($aliases, $code.' '.$name, 3);
            $this->add($aliases, $name.' '.$code, 2);
        }

        $replacements = [
            '/\bcomfort room\b/iu' => 'Restroom',
            '/\blaboratory\b/iu' => 'Lab',
            '/\blec(?:ture)?\b/iu' => 'Lecture',
            '/\bfacaulty\b/iu' => 'Faculty',
            '/\bstudent t affairs\b/iu' => 'Student Affairs',
            '/\bhead quality\s*\/\s*assurance\b/iu' => 'Quality Assurance',
            '/\bnatsci\b/iu' => 'Natural Science',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $variant = preg_replace($pattern, $replacement, $name) ?: '';
            if ($this->normalize($variant) !== $this->normalize($name)) {
                $this->add($aliases, $variant, 2);
            }
        }

        if (preg_match('/\bmale comfort room\b/iu', $name)) {
            $this->add($aliases, 'Male CR', 2);
            $this->add($aliases, "Men's Restroom", 2);
            $this->add($aliases, "Men's Toilet", 2);
        } elseif (preg_match('/\bfemale comfort room\b/iu', $name)) {
            $this->add($aliases, 'Female CR', 2);
            $this->add($aliases, "Women's Restroom", 2);
            $this->add($aliases, "Ladies' Restroom", 2);
        }

        $acronym = $this->acronym($name);
        if (mb_strlen($acronym) >= 2) {
            $this->add($aliases, $acronym, 1);
        }

        return array_filter(
            $aliases,
            fn (array $candidate) => $this->apiSearchableText($candidate['keyword']) !== ''
        );
    }

    /**
     * @param  Collection<int, IndoorRoom>  $rooms
     * @param  Collection<int, array<string, array{keyword:string, priority:int}>>  $candidates
     * @return array<string, mixed>
     */
    private function aliasUsage(
        Collection $rooms,
        Collection $candidates,
        bool $groupByBuilding = false,
        bool $groupByFloor = false
    ): array {
        $usage = [];

        foreach ($rooms as $room) {
            $buildingId = $room->indoorMap?->building_id;
            if (! $buildingId) {
                continue;
            }

            $group = $groupByBuilding ? (string) $buildingId : null;
            if ($groupByFloor) {
                $group .= '|'.$this->floorIdentity($room);
            }

            foreach (array_keys($candidates->get($room->id, [])) as $alias) {
                if ($group === null) {
                    $usage[$alias][$room->id] = true;
                } else {
                    $usage[$group][$alias][$room->id] = true;
                }
            }
        }

        return $usage;
    }

    /**
     * @return list<string>
     */
    private function buildingQualifiers(?string $name): array
    {
        $candidates = $this->buildingCandidates($name);
        $qualifiers = [];

        foreach ($candidates as $candidate) {
            $keyword = $candidate['keyword'];
            if (mb_strlen($keyword) < 2) {
                continue;
            }

            $qualifiers[$this->normalize($keyword)] = $keyword;
            if (count($qualifiers) === 3) {
                break;
            }
        }

        return array_values($qualifiers);
    }

    private function persist(
        string $type,
        int $destinationId,
        array $candidate,
        int &$created,
        int &$existing
    ): void {
        $keyword = $this->clean($candidate['keyword'] ?? '');

        if ($keyword === '') {
            return;
        }

        $duplicate = DestinationKeyword::query()
            ->where('destination_type', $type)
            ->where('destination_id', $destinationId)
            ->whereRaw('LOWER(keyword) = ?', [mb_strtolower($keyword)])
            ->exists();

        if ($duplicate) {
            $existing++;

            return;
        }

        DestinationKeyword::create([
            'keyword' => $keyword,
            'destination_type' => $type,
            'destination_id' => $destinationId,
            'priority' => max(1, min(3, (int) ($candidate['priority'] ?? 1))),
            'is_active' => true,
        ]);

        $created++;
    }

    private function floorIdentity(IndoorRoom $room): string
    {
        return (string) ($room->indoorMap?->floor_label ?: $room->indoor_map_id);
    }

    /**
     * @param  array<string, array{keyword:string, priority:int}>  $aliases
     */
    private function add(array &$aliases, ?string $keyword, int $priority): void
    {
        $keyword = $this->clean($keyword);
        $normalized = $this->normalize($keyword);

        if ($normalized === '' || mb_strlen($normalized) < 2) {
            return;
        }

        if (! isset($aliases[$normalized]) || $priority > $aliases[$normalized]['priority']) {
            $aliases[$normalized] = [
                'keyword' => $keyword,
                'priority' => $priority,
            ];
        }
    }

    /**
     * @param  array<string, array{keyword:string, priority:int}>  $aliases
     */
    private function addAmpersandVariants(array &$aliases, string $keyword, int $priority): void
    {
        if (str_contains($keyword, '&')) {
            $this->add($aliases, str_replace('&', 'and', $keyword), $priority);
        } elseif (preg_match('/\band\b/iu', $keyword)) {
            $this->add($aliases, preg_replace('/\band\b/iu', '&', $keyword), $priority);
        }
    }

    private function acronym(string $value): string
    {
        $words = preg_split('/[^\pL\pN]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ignored = ['and', 'of', 'the', 'for', 'building', 'hall', 'room', 'office'];
        $letters = [];

        foreach ($words as $word) {
            if (in_array(mb_strtolower($word), $ignored, true)) {
                continue;
            }

            $letters[] = mb_strtoupper(mb_substr($word, 0, 1));
        }

        return implode('', $letters);
    }

    private function clean(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?: '');
    }

    private function normalize(?string $value): string
    {
        $value = mb_strtolower($this->clean($value));
        $value = str_replace('&', ' and ', $value);

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $value) ?: '');
    }

    /**
     * Mirrors the generic words removed by ApiController::searchDestination.
     */
    private function apiSearchableText(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['room', 'office'], ' ', $value);

        return trim(preg_replace('/[^a-z0-9]+/iu', ' ', $value) ?: '');
    }
}
