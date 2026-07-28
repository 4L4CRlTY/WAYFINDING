<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'title',
        'destination_type',
        'destination_id',
        'campus_event_id',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'destination_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campusEvent()
    {
        return $this->belongsTo(CampusEvent::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'destination_id');
    }

    public function indoorRoom()
    {
        return $this->belongsTo(IndoorRoom::class, 'destination_id');
    }

    public function landuse()
    {
        return $this->belongsTo(Landuse::class, 'destination_id');
    }

    public function destination()
    {
        return match ($this->destination_type) {
            'room' => $this->indoorRoom,
            'landuse' => $this->landuse,
            default => $this->building,
        };
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->campus_event_id) {
            return true;
        }

        $event = $this->campusEvent;

        if (! $event || ! $event->is_active) {
            return false;
        }

        return ! $event->ends_at || $event->ends_at->isFuture();
    }

    public function destinationLabel(): string
    {
        if ($this->destination_type === 'room') {
            $room = $this->indoorRoom;
            $building = $room?->indoorMap?->building?->name;
            $roomLabel = trim(implode(' · ', array_filter([
                $room?->room_code,
                $room?->name,
            ])));

            return trim(implode(' — ', array_filter([$building, $roomLabel])))
                ?: 'Unavailable room';
        }

        if ($this->destination_type === 'landuse') {
            return $this->landuse?->name ?? 'Unavailable land-use area';
        }

        return $this->building?->name ?? 'Unavailable building';
    }
}
