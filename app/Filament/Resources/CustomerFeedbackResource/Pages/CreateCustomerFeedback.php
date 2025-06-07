<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use App\Filament\Resources\CustomerFeedbackResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerFeedback extends CreateRecord
{
    protected static string $resource = CustomerFeedbackResource::class;

    protected function getTitle(): string
    {
        return __('Submit Feedback');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Feedback submitted successfully');
    }

    protected function getCreatedNotificationMessage(): ?string
    {
        return __('Your feedback has been submitted and will be reviewed by our team.');
    }

    protected function beforeCreate(): void
    {
        // Validate user has access to selected project
        $user = auth()->user();
        $projectId = $this->data['project_id'];

        if ($user->hasRole('Client')) {
            $ownedProjectIds = $user->projectsOwning()->pluck('id');
            $attachedProjectIds = $user->projectsAffected()->pluck('projects.id');
            $accessibleProjectIds = $ownedProjectIds->merge($attachedProjectIds)->unique();

            if (!$accessibleProjectIds->contains($projectId)) {
                $this->halt();
                $this->notify('danger', 'You do not have access to this project.');
            }
        }
    }
}
