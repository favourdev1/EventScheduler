<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'max_participants',
        'status',
        'is_private',
        'category_id',
        'organizer_id',
        'timezone',
        'cancellation_reason'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_private' => 'boolean',
        'max_participants' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(EventCategory::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_registrations')
                    ->withPivot('status', 'cancelled_at', 'cancellation_reason')
                    ->withTimestamps();
    }

    public function getActiveParticipantsCountAttribute()
    {
        return $this->registrations()
                    ->where('status', 'registered')
                    ->count();
    }

    public function hasAvailableSpots(): bool
    {
        return $this->active_participants_count < $this->max_participants;
    }

    public function isUpcoming(): bool
    {
        return $this->start_time->isFuture();
    }

    public function isOngoing(): bool
    {
        $now = Carbon::now();
        return $this->start_time->isPast() && $this->end_time->isFuture();
    }

    public function isCompleted(): bool
    {
        return $this->end_time->isPast();
    }

    public function updateStatus(): void
    {
        $newStatus = match(true) {
            $this->status === 'cancelled' => 'cancelled',
            $this->isCompleted() => 'completed',
            $this->isOngoing() => 'ongoing',
            $this->isUpcoming() => 'upcoming',
            default => $this->status
        };

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    protected static function booted()
    {
        static::retrieved(function ($event) {
            $event->updateStatus();
        });
    }
}
