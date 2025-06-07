<?php

namespace App\Models;

use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusUpdated;
use App\Notifications\FeedbackUpdated;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\TicketHour;

class Ticket extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name', 'content', 'owner_id', 'responsible_id',
        'status_id', 'project_id', 'code', 'order', 'type_id',
        'priority_id', 'estimation', 'epic_id', 'sprint_id', 'due_date'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Ticket $item) {
            $project = Project::where('id', $item->project_id)->first();
            $count = Ticket::where('project_id', $project->id)->count();
            $order = $project->tickets?->last()?->order ?? -1;
            $item->code = $project->ticket_prefix . '-' . ($count + 1);
            $item->order = $order + 1;
        });

        static::created(function (Ticket $item) {
            if ($item->sprint_id && $item->sprint->epic_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => $item->sprint->epic_id]);
            }

            foreach ($item->watchers as $user) {
                $user->notify(new TicketCreated($item));
            }

            // Jika ticket ini dibuat dari customer feedback, notify customer
            if ($item->customerFeedback) {
                $feedback = $item->customerFeedback;
                $feedback->user->notify(new FeedbackUpdated(
                    $feedback,
                    "Your feedback has been converted to ticket: {$item->code}"
                ));
            }
        });

        static::updating(function (Ticket $item) {
            $old = Ticket::where('id', $item->id)->first();

            // Ticket activity based on status
            $oldStatus = $old->status_id;
            if ($oldStatus != $item->status_id) {
                TicketActivity::create([
                    'ticket_id' => $item->id,
                    'old_status_id' => $oldStatus,
                    'new_status_id' => $item->status_id,
                    'user_id' => auth()->user()->id
                ]);

                foreach ($item->watchers as $user) {
                    $user->notify(new TicketStatusUpdated($item));
                }

                // Jika ticket ini dari customer feedback, notify customer juga
                if ($item->customerFeedback) {
                    $feedback = $item->customerFeedback;
                    $newStatusName = $item->status->name;
                    $feedback->user->notify(new FeedbackUpdated(
                        $feedback,
                        "Ticket {$item->code} status updated to: {$newStatusName}"
                    ));
                }
            }

            // Ticket sprint update
            $oldSprint = $old->sprint_id;
            if ($oldSprint && !$item->sprint_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => null]);
            } elseif ($item->sprint_id && $item->sprint->epic_id) {
                Ticket::where('id', $item->id)->update(['epic_id' => $item->sprint->epic_id]);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id', 'id')->withTrashed();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'type_id', 'id')->withTrashed();
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id', 'id')->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')->withTrashed();
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class, 'epic_id', 'id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'sprint_id', 'id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class, 'ticket_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(TicketRelation::class, 'ticket_id', 'id');
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers', 'ticket_id', 'user_id');
    }

    // Relasi ke CustomerFeedback (reverse relationship)
    public function customerFeedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class, 'converted_ticket_id', 'id');
    }

    /**
     * Ticket Hours relationship
     */
    public function hours(): HasMany
    {
        return $this->hasMany(TicketHour::class, 'ticket_id', 'id');
    }

    /**
     * Total logged hours attribute
     */
    public function totalLoggedHours(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value');
            }
        );
    }

    /**
     * Estimation progress percentage
     */
    public function estimationProgress(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->estimation || $this->estimation == 0) {
                    return 0;
                }
                return ($this->totalLoggedHours / $this->estimation) * 100;
            }
        );
    }
    public function estimationForHumans(): Attribute
    {
        return new Attribute(
            get: function () {
                return CarbonInterval::seconds($this->estimationInSeconds)->cascade()->forHumans();
            }
        );
    }

    public function estimationInSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->estimation) {
                    return null;
                }
                return $this->estimation * 3600;
            }
        );
    }
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_subscribers', 'ticket_id', 'user_id');
    }

    public function totalLoggedSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value') * 3600;
            }
        );
    }

    public function totalLoggedInHours(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value');
            }
        );
    }

    /**
     * Completed at attribute (when ticket was completed)
     */
    public function completedAt(): Attribute
    {
        return new Attribute(
            get: function () {
                // Get the latest activity where status changed to a "completed" status
                $completedActivity = $this->activities()
                    ->whereHas('newStatus', function ($query) {
                        $query->where('name', 'like', '%completed%')
                              ->orWhere('name', 'like', '%done%')
                              ->orWhere('name', 'like', '%finished%');
                    })
                    ->latest()
                    ->first();

                return $completedActivity ? $completedActivity->created_at : null;
            }
        );
    }

    public function completudePercentage(): Attribute
    {
        return new Attribute(
            get: fn() => $this->estimationProgress
        );
    }

    /**
     * Diff for humans when completed
     */
    public function diffForHumans(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->completedAt ? $this->completedAt->diffForHumans() : null;
            }
        );
    }

    /**
     * Check if ticket is completed
     */
    public function isCompleted(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->status && (
                    stripos($this->status->name, 'completed') !== false ||
                    stripos($this->status->name, 'done') !== false ||
                    stripos($this->status->name, 'finished') !== false ||
                    stripos($this->status->name, 'closed') !== false
                );
            }
        );
    }
    public function daysUntilDue(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->due_date) {
                    return null;
                }
                return now()->diffInDays($this->due_date, false);
            }
        );
    }
    public function ccUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_cc', 'ticket_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Get watchers who should be notified
     */
    public function getNotifiableWatchers()
    {
        $watchers = collect();

        // Add owner
        if ($this->owner) {
            $watchers->push($this->owner);
        }

        // Add responsible user
        if ($this->responsible) {
            $watchers->push($this->responsible);
        }

        // Add explicit watchers
        $watchers = $watchers->merge($this->watchers);

        // Remove duplicates
        return $watchers->unique('id');
    }
    public function scopeCompletedBetween($query, $startDate, $endDate)
    {
        return $query->whereHas('activities', function($q) use ($startDate, $endDate) {
            $q->whereHas('newStatus', function($sq) {
                $sq->where('name', 'Completed');
            })
            ->whereBetween('created_at', [$startDate, $endDate]);
        });
    }

    public function scopeCompletedToday($query)
    {
        return $query->completedBetween(
            now()->startOfDay(),
            now()->endOfDay()
        );
    }
}
