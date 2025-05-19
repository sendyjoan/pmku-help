<?php

namespace App\Http\Livewire;

use Filament\Forms;
use Illuminate\Support\HtmlString;
use JeffGreco13\FilamentBreezy\Pages\MyProfile as BaseProfile;
use App\Models\User;
use Livewire\WithFileUploads;
use App\Services\CloudinaryService;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class Profile extends BaseProfile
{
    use WithFileUploads;

    protected static ?string $slug = 'my-profile';

    // Add property for avatar upload
    public $avatar;

    // Add listeners for avatar upload and page scripts
    protected $listeners = [
        'upload:finished' => 'handleAvatarUpload'
    ];

    public function mount(): void
    {
        parent::mount();

        // Add script to handle page refresh
        $this->dispatchBrowserEvent('profile-scripts', [
            'script' => "
                document.addEventListener('refresh-page', function() {
                    window.location.reload();
                });
            "
        ]);
    }

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

        // Add profile picture field at the beginning
        $avatarField = Forms\Components\FileUpload::make('avatar')
            ->label(__('Profile Picture'))
            ->image()
            ->disk('public')
            ->directory('avatars')
            ->visibility('public')
            ->maxSize(5120) // 5MB max
            ->imageResizeMode('cover')
            ->imageCropAspectRatio('1:1')
            ->imageResizeTargetWidth('200')
            ->imageResizeTargetHeight('200')
            ->placeholder(function () {
                if ($this->user->avatar_url) {
                    return 'Current profile picture is using Cloudinary';
                }
                return 'No profile picture set';
            })
            // Add events to auto-save when upload complete
            ->uploadProgressIndicatorPosition('left')
            ->uploadButtonPosition('left')
            ->loadingIndicatorPosition('left')
            ->removeUploadedFileButtonPosition('right')
            ->enableOpen()
            ->afterStateUpdated(function ($state) {
                if ($state) {
                    $this->uploadAvatar($state);
                }
            });

        // Custom avatar preview
        $avatarPreview = Forms\Components\View::make('components.profile.avatar-preview')
            ->visible(fn () => $this->user->avatar_url !== null)
            ->label('Current Avatar');

        // Insert fields into the form
        array_splice($fields, 0, 0, [$avatarPreview, $avatarField]); // Add avatar fields at the beginning
        array_splice($fields, 3, 0, [$usernameField]); // Add username after name (now at position 3)

        // Update email field helper text (now at position 4)
        $emailFieldIndex = 4;
        if (isset($fields[$emailFieldIndex])) {
            $fields[$emailFieldIndex]->helperText(function () {
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
        }

        return $fields;
    }

    protected function getFormModel(): User
    {
        return $this->user;
    }

    // Override the getUpdateProfileFormValidationRules method to add the ignoreRecord rule for username
    protected function getUpdateProfileFormValidationRules(): array
    {
        $rules = parent::getUpdateProfileFormValidationRules();

        // Replace the username validation rule to ignore the current user
        if (isset($rules['username'])) {
            $rules['username'] = [
                'required',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($this->user->id)
            ];
        }

        return $rules;
    }

    // This method will handle auto-upload when a file is selected
    public function uploadAvatar($avatar)
    {
        if (!$avatar) {
            return;
        }

        try {
            // Use the CloudinaryService to upload the image
            $cloudinaryService = new CloudinaryService();
            $result = $cloudinaryService->uploadImage($avatar);

            // Save the Cloudinary URL to the user's avatar_url field
            if (isset($result['secure_url'])) {
                $this->user->update(['avatar_url' => $result['secure_url']]);
                $this->user->refresh();
                $this->notify('success', __('Foto profil berhasil diupload'));

                // Refresh the page to show the new avatar
                $this->emit('refresh');
                $this->dispatchBrowserEvent('refresh-page');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to upload avatar to Cloudinary: ' . $e->getMessage());
            $this->notify('error', __('Upload foto profil gagal. Silakan coba lagi.'));
        }
    }

    // Handle when upload is finished
    public function handleAvatarUpload($upload)
    {
        if (isset($upload['avatar']) && $upload['avatar']) {
            $this->uploadAvatar($upload['avatar']);
        }
    }

    public function updateProfile()
    {
        // Validate form data
        $data = $this->updateProfileForm->getState();

        // Handle username update
        if (isset($data['username'])) {
            // Clean username
            $data['username'] = strtolower(trim($data['username']));
        }

        $loginColumnValue = $data[$this->loginColumn];
        unset($data[$this->loginColumn]);

        // Remove the avatar field as we handle it separately
        unset($data['avatar']);

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

        $this->notify("success", __('Profil berhasil diperbarui'));
    }

    public function resendPending(): void
    {
        $this->user->resendPendingEmailVerificationMail();
        $this->notify('success', __('Email verifikasi telah dikirim'));
    }

    /**
     * Remove the user's avatar
     */
    public function removeAvatar()
    {
        $this->user->update(['avatar_url' => null]);
        $this->user->refresh();
        $this->notify('success', __('Foto profil berhasil dihapus'));

        // Refresh the page to update the avatar preview
        $this->dispatchBrowserEvent('refresh-page');
    }
}
