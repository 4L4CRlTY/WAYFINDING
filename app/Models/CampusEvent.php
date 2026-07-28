<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampusEvent extends Model
{
    use HasFactory;

    protected $table = 'campus_events';

    protected $fillable = [
        'event_target_type',
        'building_id',
        'indoor_room_id',
        'landuse_id',
        'created_by',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location_label',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Event belongs directly to a building.
     */
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Event belongs to a specific indoor room.
     */
    public function indoorRoom()
    {
        return $this->belongsTo(IndoorRoom::class, 'indoor_room_id');
    }

    /**
     * Event belongs to landuse/open area.
     */
    public function landuse()
    {
        return $this->belongsTo(Landuse::class);
    }

    /**
     * Admin/authorized user/public user nga naghimo sa event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function destinationLink()
    {
        return $this->hasOne(DestinationLink::class);
    }

    /**
     * Active events only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Events happening right now.
     */
    public function scopeHappeningNow($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', Carbon::now());
    }

    /**
     * Current or upcoming events.
     */
    public function scopeCurrentOrUpcoming($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where(function ($current) use ($now) {
                    $current->where('starts_at', '<=', $now)
                        ->where(function ($end) use ($now) {
                            $end->whereNull('ends_at')
                                ->orWhere('ends_at', '>=', $now);
                        });
                })
                    ->orWhere('starts_at', '>', $now);
            });
    }

    /**
     * Para dali mahibaw-an sa frontend kung event karon or upcoming.
     */
    public function getEventStatusAttribute(): string
    {
        $now = Carbon::now();

        if ($this->starts_at && $this->starts_at->lte($now)) {
            if (! $this->ends_at || $this->ends_at->gte($now)) {
                return 'happening_now';
            }
        }

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return 'upcoming';
        }

        return 'ended';
    }

    /**
     * Useful display label.
     */
    public function getTargetLabelAttribute(): string
    {
        if ($this->event_target_type === 'building') {
            return optional($this->building)->name ?? 'Building Event';
        }

        if ($this->event_target_type === 'room') {
            $room = $this->indoorRoom;

            if ($room) {
                return trim(($room->room_code ? $room->room_code.' - ' : '').($room->name ?? 'Room Event'));
            }

            return 'Room Event';
        }

        if ($this->event_target_type === 'landuse') {
            return optional($this->landuse)->name ?? 'Landuse Event';
        }

        return 'Campus Event';
    }
}
