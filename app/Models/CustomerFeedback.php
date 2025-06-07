<?php

namespace App\Models;

use App\Notifications\FeedbackSubmitted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerFeedback extends Model
{
    use HasFactory;
    protected $table = 'customer_feedbacks';
    protected $fillable = [
        'project_id', 'user_id', 'title', 'description',
        'status', 'converted_ticket_id'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function (CustomerFeedback $feedback) {
            // Log activity ketika feedback dibuat
            CustomerFeedbackActivity::create([
                'feedback_id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'action' => 'submitted',
                'notes' => 'Customer feedback submitted'
            ]);

            // Notify admin & super admin
            $admins = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'Admin']);
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new FeedbackSubmitted($feedback));
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convertedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'converted_ticket_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerFeedbackActivity::class, 'feedback_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>',
            'converted_to_ticket' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Converted to Ticket</span>',
            'rejected' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>',
            default => $this->status
        };
    }
}