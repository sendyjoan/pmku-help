<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use App\Filament\Resources\CustomerFeedbackResource;
use App\Models\CustomerFeedbackActivity;
use App\Notifications\FeedbackUpdated;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerFeedback extends EditRecord
{
    protected static string $resource = CustomerFeedbackResource::class;

    protected function getActions(): array
    {
        $actions = [];

        $actions[] = Actions\ViewAction::make();

        if (auth()->user()->hasRole(['Super Admin', 'Admin'])) {
            $actions[] = Actions\DeleteAction::make();
        }

        return $actions;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Log perubahan status jika ada
        if (isset($data['status']) && $data['status'] !== $this->record->status) {
            $oldStatus = $this->record->status;
            $newStatus = $data['status'];

            CustomerFeedbackActivity::create([
                'feedback_id' => $this->record->id,
                'user_id' => auth()->id(),
                'action' => 'status_changed',
                'notes' => "Status changed from {$oldStatus} to {$newStatus}"
            ]);

            // Notify customer jika status berubah
            if ($newStatus === 'rejected') {
                $this->record->user->notify(new FeedbackUpdated($this->record, 'Your feedback has been reviewed and rejected.'));
            }
        }

        // Log perubahan title jika ada
        if (isset($data['title']) && $data['title'] !== $this->record->title) {
            CustomerFeedbackActivity::create([
                'feedback_id' => $this->record->id,
                'user_id' => auth()->id(),
                'action' => 'title_updated',
                'notes' => "Title updated from '{$this->record->title}' to '{$data['title']}'"
            ]);
        }

        // Log perubahan description jika ada
        if (isset($data['description']) && $data['description'] !== $this->record->description) {
            CustomerFeedbackActivity::create([
                'feedback_id' => $this->record->id,
                'user_id' => auth()->id(),
                'action' => 'description_updated',
                'notes' => "Description updated"
            ]);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('Feedback updated successfully');
    }

    protected function getTitle(): string
    {
        return __('Edit Feedback: :title', ['title' => $this->record->title]);
    }

    protected function getBreadcrumbs(): array
    {
        return [
            $this->getResource()::getUrl() => $this->getResource()::getBreadcrumb(),
            $this->getResource()::getUrl('view', ['record' => $this->record]) => $this->record->title,
            '' => __('Edit'),
        ];
    }

    protected function afterSave(): void
    {
        // Refresh record to get latest data
        $this->record->refresh();

        // Send general update notification to customer if admin/super admin made changes
        if (auth()->user()->hasRole(['Super Admin', 'Admin']) && auth()->id() !== $this->record->user_id) {
            $this->record->user->notify(new FeedbackUpdated(
                $this->record,
                'Your feedback has been updated by our team.'
            ));
        }
    }

    protected function beforeSave(): void
    {
        // Validate permissions
        $user = auth()->user();

        if ($user->hasRole('Client')) {
            // Client can only edit their own pending feedback
            if ($this->record->user_id !== $user->id || $this->record->status !== 'pending') {
                $this->halt();
                $this->notify('danger', 'You cannot edit this feedback.');
            }
        }
    }
}