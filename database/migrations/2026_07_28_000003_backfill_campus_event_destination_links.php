<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('campus_events')
            ->whereIn('event_target_type', ['building', 'room', 'landuse'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('destination_links')
                    ->whereColumn('destination_links.campus_event_id', 'campus_events.id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($events) use ($now) {
                $links = [];

                foreach ($events as $event) {
                    $destinationId = match ($event->event_target_type) {
                        'room' => $event->indoor_room_id,
                        'landuse' => $event->landuse_id,
                        default => $event->building_id,
                    };

                    if (! $destinationId) {
                        continue;
                    }

                    $eventHasNotEnded = ! $event->ends_at || Carbon::parse($event->ends_at)->isFuture();

                    $links[] = [
                        'token' => Str::random(40),
                        'title' => $event->title,
                        'destination_type' => $event->event_target_type,
                        'destination_id' => $destinationId,
                        'campus_event_id' => $event->id,
                        'created_by' => $event->created_by,
                        'is_active' => (bool) $event->is_active && $eventHasNotEnded,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($links !== []) {
                    DB::table('destination_links')->insert($links);
                }
            }, 'campus_events.id', 'id');
    }

    public function down(): void
    {
        // Existing event links are retained to avoid deleting links used by visitors.
    }
};
