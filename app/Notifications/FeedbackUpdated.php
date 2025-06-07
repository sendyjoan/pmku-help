<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CustomerFeedback;
use Filament\Notifications\Notification as FilamentNotification;
use App\Models\User;
use Filament\Notifications\Actions\Action;

class FeedbackUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private CustomerFeedback $feedback;
    private string $updateMessage;

    public function __construct(CustomerFeedback $feedback, string $updateMessage)
    {
        $this->feedback = $feedback;
        $this->updateMessage = $updateMessage;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line(__('Your feedback has been updated.'))
            ->line('- ' . __('Feedback:') . ' ' . $this->feedback->title)
            ->line('- ' . __('Project:') . ' ' . $this->feedback->project->name)
            ->line('- ' . __('Update:') . ' ' . $this->updateMessage)
            ->action(__('View Feedback'), route('filament.resources.customer-feedbacks.view', $this->feedback->id));
    }

    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('Feedback Updated'))
            ->icon('heroicon-o-annotation')
            ->body(fn() => $this->updateMessage)
            ->getDatabaseMessage();
    }
}
