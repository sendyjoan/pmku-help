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

class FeedbackConverted extends Notification implements ShouldQueue
{
    use Queueable;

    private CustomerFeedback $feedback;

    public function __construct(CustomerFeedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line(__('Your feedback has been converted to a ticket.'))
            ->line('- ' . __('Feedback:') . ' ' . $this->feedback->title)
            ->line('- ' . __('Project:') . ' ' . $this->feedback->project->name)
            ->line('- ' . __('Ticket Code:') . ' ' . $this->feedback->convertedTicket->code)
            ->line(__('You will receive updates as the ticket progresses.'))
            ->action(__('View Ticket'), route('filament.resources.tickets.share', $this->feedback->convertedTicket->code));
    }

    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('Feedback Converted to Ticket'))
            ->icon('heroicon-o-ticket')
            ->body(fn() => $this->feedback->title)
            ->actions([
                Action::make('view')
                    ->link()
                    ->icon('heroicon-s-eye')
                    ->url(fn() => route('filament.resources.tickets.share', $this->feedback->convertedTicket->code)),
            ])
            ->getDatabaseMessage();
    }
}
