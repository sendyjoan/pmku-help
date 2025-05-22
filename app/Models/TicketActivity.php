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
                // Safe null checks
                $oldStatusName = $this->oldStatus?->name ?? 'Unknown Status';
                $newStatusName = $this->newStatus?->name ?? 'Unknown Status';

                $description = "changed status from {$oldStatusName} to {$newStatusName}";

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

    // Scope untuk filter activities yang valid
    public function scopeValidStatuses($query)
    {
        return $query->whereNotNull('old_status_id')
                    ->whereNotNull('new_status_id')
                    ->whereHas('oldStatus')
                    ->whereHas('newStatus');
    }

    // Scope untuk activities dengan user yang valid
    public function scopeWithValidUser($query)
    {
        return $query->whereHas('user');
    }
}
