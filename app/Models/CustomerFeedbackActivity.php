<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedbackActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_id', 'user_id', 'action', 'notes'
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(CustomerFeedback::class, 'feedback_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'submitted' => 'Feedback Submitted',
            'converted_to_ticket' => 'Converted to Ticket',
            'rejected' => 'Feedback Rejected',
            'noted' => 'Note Added',
            default => ucfirst(str_replace('_', ' ', $this->action))
        };
    }
}
