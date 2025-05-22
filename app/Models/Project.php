<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name', 'description', 'status_id', 'owner_id', 'ticket_prefix',
        'status_type', 'type', 'auto_complete_enabled', 'auto_complete_days',
        'auto_complete_from_status', 'auto_complete_to_status'
    ];

    protected $appends = [
        'cover'
    ];

    protected $casts = [
        'auto_complete_enabled' => 'boolean',
        'auto_complete_days' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id', 'id')->withTrashed();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id')->withPivot(['role']);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'project_id', 'id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(TicketStatus::class, 'project_id', 'id');
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class, 'project_id', 'id');
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class, 'project_id', 'id');
    }

    public function epicsFirstDate(): Attribute
    {
        return new Attribute(
            get: function () {
                $firstEpic = $this->epics()->orderBy('starts_at')->first();
                if ($firstEpic) {
                    return $firstEpic->starts_at;
                }
                return now();
            }
        );
    }

    public function epicsLastDate(): Attribute
    {
        return new Attribute(
            get: function () {
                $firstEpic = $this->epics()->orderBy('ends_at', 'desc')->first();
                if ($firstEpic) {
                    return $firstEpic->ends_at;
                }
                return now();
            }
        );
    }

    public function contributors(): Attribute
    {
        return new Attribute(
            get: function () {
                $users = $this->users;
                $users->push($this->owner);
                return $users->unique('id');
            }
        );
    }

    public function cover(): Attribute
    {
        return new Attribute(
            get: fn() => $this->getFirstMediaUrl('cover')
                ??
                'https://ui-avatars.com/api/?background=3f84f3&color=ffffff&name=' . $this->name
        );
    }

    public function currentSprint(): Attribute
    {
        return new Attribute(
            get: fn() => $this->sprints()
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->first()
        );
    }

    public function nextSprint(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->currentSprint) {
                    return $this->sprints()
                        ->whereNull('started_at')
                        ->whereNull('ended_at')
                        ->where('starts_at', '>=', $this->currentSprint->ends_at)
                        ->orderBy('starts_at')
                        ->first();
                }
                return null;
            }
        );
    }

    /**
     * Get tickets that are eligible for auto completion
     */
    public function getTicketsForAutoCompletion()
    {
        if (!$this->auto_complete_enabled || !$this->auto_complete_from_status) {
            return collect();
        }

        // Get the target status
        $fromStatus = $this->getAutoCompleteFromStatus();
        if (!$fromStatus) {
            return collect();
        }

        // Get tickets that have been in the target status for too long
        $cutoffDate = now()->subDays($this->auto_complete_days);

        return $this->tickets()
            ->where('status_id', $fromStatus->id)
            ->whereHas('activities', function ($query) use ($fromStatus, $cutoffDate) {
                $query->where('new_status_id', $fromStatus->id)
                    ->where('created_at', '<=', $cutoffDate)
                    ->whereNotExists(function ($subQuery) use ($fromStatus) {
                        $subQuery->selectRaw(1)
                            ->from('ticket_activities as ta2')
                            ->whereColumn('ta2.ticket_id', 'ticket_activities.ticket_id')
                            ->where('ta2.new_status_id', '!=', $fromStatus->id)
                            ->whereColumn('ta2.created_at', '>', 'ticket_activities.created_at');
                    });
            })
            ->get();
    }

    /**
     * Get the "from" status object for auto completion
     */
    public function getAutoCompleteFromStatus()
    {
        if (!$this->auto_complete_from_status) {
            return null;
        }

        if ($this->status_type === 'custom') {
            return $this->statuses()->where('name', $this->auto_complete_from_status)->first();
        } else {
            return TicketStatus::whereNull('project_id')
                ->where('name', $this->auto_complete_from_status)
                ->first();
        }
    }

    /**
     * Get the "to" status object for auto completion
     */
    public function getAutoCompleteToStatus()
    {
        if (!$this->auto_complete_to_status) {
            return null;
        }

        if ($this->status_type === 'custom') {
            return $this->statuses()->where('name', $this->auto_complete_to_status)->first();
        } else {
            return TicketStatus::whereNull('project_id')
                ->where('name', $this->auto_complete_to_status)
                ->first();
        }
    }

    /**
     * Get available statuses for auto complete options
     */
    public function getAvailableStatuses()
    {
        if ($this->status_type === 'custom') {
            return $this->statuses()->pluck('name', 'name')->toArray();
        } else {
            return TicketStatus::whereNull('project_id')
                ->pluck('name', 'name')
                ->toArray();
        }
    }
}
