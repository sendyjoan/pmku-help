<?php

namespace App\Http\Livewire;

use Filament\Forms;
use Illuminate\Support\HtmlString;
use JeffGreco13\FilamentBreezy\Pages\MyProfile as BaseProfile;
use App\Models\User;

class Profile extends BaseProfile
{

    protected static ?string $slug = 'my-profile';

    protected function getUpdateProfileFormSchema(): array
    {
        $fields = parent::getUpdateProfileFormSchema();

        // Add username field after name field (position 1)
        $usernameField = Forms\Components\TextInput::make('username')
            ->label(__('Username'))
            ->unique(User::class, 'username', ignoreRecord: true)
            ->helperText(__('Your unique username for mentions (@username). Only letters, numbers, and underscores allowed.'))
            ->required()
            ->rules(['regex:/^[a-zA-Z0-9_]+$/']);

        // Insert username field after name (position 1)
        array_splice($fields, 1, 0, [$usernameField]);

        // Update email field helper text
        $fields[2]->helperText(function () {
            $pendingEmail = $this->user->getPendingEmail();
            if ($pendingEmail) {
                return new HtmlString(
                    '<span>' .
                    __('You have a pending email verification for :email.', [
                        'email' => $pendingEmail
                    ])
                    . '</span> <a wire:click="resendPending"
                                   class="hover:cursor-pointer hover:text-primary-500 hover:underline">
                    ' . __('Click here to resend') . '
                </a>'
                );
            } else {
                return '';
            }
        });

        return $fields;
    }

    public function updateProfile()
    {
        $data = $this->updateProfileForm->getState();

        // Handle username update
        if (isset($data['username'])) {
            // Clean username
            $data['username'] = strtolower(trim($data['username']));
        }

        $loginColumnValue = $data[$this->loginColumn];
        unset($data[$this->loginColumn]);

        $this->user->update($data);
        $this->user->refresh();

        // Update form with latest data including username
        $this->updateProfileForm->fill([
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email
        ]);

        if ($loginColumnValue != $this->user->{$this->loginColumn}) {
            $this->user->newEmail($loginColumnValue);
        }

        $this->notify("success", __('filament-breezy::default.profile.personal_info.notify'));
    }

    public function resendPending(): void
    {
        $this->user->resendPendingEmailVerificationMail();
        $this->notify('success', __('Email verification sent'));
    }
}
