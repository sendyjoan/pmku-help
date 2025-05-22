<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TicketActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id', 'old_status_id', 'new_status_id', 'user_id'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id')->withTrashed();
    }

    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'old_status_id', 'id')->withTrashed();
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'new_status_id', 'id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function description(): Attribute
    {
        return new Attribute(
            get: function () {
                $description = "changed status from {$this->oldStatus->name} to {$this->newStatus->name}";

                // Add auto-complete indicator for system actions
                if (!$this->user_id) {
                    $description .= " (auto-completed by system)";
                }

                return $description;
            }
        );
    }

    public function formattedDate(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->created_at->format('M d, Y \a\t g:i A');
            }
        );
    }

    public function isSystemAction(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->user_id === null;
            }
        );
    }

    public function actorName(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->user ? $this->user->name : 'System';
            }
        );
    }
}
