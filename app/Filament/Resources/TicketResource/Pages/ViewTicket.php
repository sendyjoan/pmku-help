<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Exports\TicketHoursExport;
use App\Filament\Resources\TicketResource;
use App\Models\Activity;
use App\Models\TicketComment;
use App\Models\TicketHour;
use App\Models\TicketSubscriber;
use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Columns\TextColumn;

class ViewTicket extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament.resources.tickets.view';

    public string $tab = 'comments';

    protected $listeners = ['doDeleteComment'];

    public $selectedCommentId;

    public function mount($record): void
    {
        parent::mount($record);
        $this->form->fill();
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('toggleSubscribe')
                ->label(
                    fn() => $this->record->subscribers()->where('users.id', auth()->user()->id)->count() ?
                        __('Unsubscribe')
                        : __('Subscribe')
                )
                ->color(
                    fn() => $this->record->subscribers()->where('users.id', auth()->user()->id)->count() ?
                        'danger'
                        : 'success'
                )
                ->icon('heroicon-o-bell')
                ->button()
                ->action(function () {
                    if (
                        $sub = TicketSubscriber::where('user_id', auth()->user()->id)
                            ->where('ticket_id', $this->record->id)
                            ->first()
                    ) {
                        $sub->delete();
                        $this->notify('success', __('You unsubscribed from the ticket'));
                    } else {
                        TicketSubscriber::create([
                            'user_id' => auth()->user()->id,
                            'ticket_id' => $this->record->id
                        ]);
                        $this->notify('success', __('You subscribed to the ticket'));
                    }
                    $this->record->refresh();
                }),
            Actions\Action::make('share')
                ->label(__('Share'))
                ->color('secondary')
                ->button()
                ->icon('heroicon-o-share')
                ->action(fn() => $this->dispatchBrowserEvent('shareTicket', [
                    'url' => route('filament.resources.tickets.share', $this->record->code)
                ])),
            Actions\EditAction::make(),
            Actions\Action::make('logHours')
                ->label(__('Log time'))
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->modalWidth('sm')
                ->modalHeading(__('Log worked time'))
                ->modalSubheading(__('Use the following form to add your worked time in this ticket.'))
                ->modalButton(__('Log'))
                ->visible(fn() => in_array(
                    auth()->user()->id,
                    [$this->record->owner_id, $this->record->responsible_id]
                ))
                ->form([
                    TextInput::make('time')
                        ->label(__('Time to log'))
                        ->numeric()
                        ->required(),
                    Select::make('activity_id')
                        ->label(__('Activity'))
                        ->searchable()
                        ->reactive()
                        ->options(function ($get, $set) {
                            return Activity::all()->pluck('name', 'id')->toArray();
                        }),
                    Textarea::make('comment')
                        ->label(__('Comment'))
                        ->rows(3),
                ])
                ->action(function (Collection $records, array $data): void {
                    $value = $data['time'];
                    $comment = $data['comment'];
                    TicketHour::create([
                        'ticket_id' => $this->record->id,
                        'activity_id' => $data['activity_id'],
                        'user_id' => auth()->user()->id,
                        'value' => $value,
                        'comment' => $comment
                    ]);
                    $this->record->refresh();
                    $this->notify('success', __('Time logged into ticket'));
                }),
            Actions\ActionGroup::make([
                Actions\Action::make('exportLogHours')
                    ->label(__('Export time logged'))
                    ->icon('heroicon-o-document-download')
                    ->color('warning')
                    ->visible(
                        fn() => $this->record->watchers->where('id', auth()->user()->id)->count()
                            && $this->record->hours()->count()
                    )
                    ->action(fn() => Excel::download(
                        new TicketHoursExport($this->record),
                        'time_' . str_replace('-', '_', $this->record->code) . '.csv',
                        \Maatwebsite\Excel\Excel::CSV,
                        ['Content-Type' => 'text/csv']
                    )),
            ])
                ->visible(fn() => (in_array(
                        auth()->user()->id,
                        [$this->record->owner_id, $this->record->responsible_id]
                    )) || (
                        $this->record->watchers->where('id', auth()->user()->id)->count()
                        && $this->record->hours()->count()
                    ))
                ->color('secondary'),
        ];
    }

    public function selectTab(string $tab): void
    {
        $this->tab = $tab;
    }

    protected function getFormSchema(): array
    {
        return [
            RichEditor::make('comment')
                ->disableLabel()
                ->placeholder(__('Type a new comment, use @username to mention users'))
                ->required()
                ->extraInputAttributes([
                    'data-enable-mentions' => 'true',
                    'id' => 'comment-editor'
                ])
        ];
    }

    public function submitComment(): void
    {
        $data = $this->form->getState();
        if ($this->selectedCommentId) {
            TicketComment::where('id', $this->selectedCommentId)
                ->update([
                    'content' => $data['comment']
                ]);
        } else {
            TicketComment::create([
                'user_id' => auth()->user()->id,
                'ticket_id' => $this->record->id,
                'content' => $data['comment']
            ]);
        }
        $this->record->refresh();
        $this->cancelEditComment();
        $this->notify('success', __('Comment saved'));
    }

    public function isAdministrator(): bool
    {
        return $this->record
                ->project
                ->users()
                ->where('users.id', auth()->user()->id)
                ->where('role', 'administrator')
                ->count() != 0;
    }

    public function editComment(int $commentId): void
    {
        $this->form->fill([
            'comment' => $this->record->comments->where('id', $commentId)->first()?->content
        ]);
        $this->selectedCommentId = $commentId;
    }

    public function deleteComment(int $commentId): void
    {
        Notification::make()
            ->warning()
            ->title(__('Delete confirmation'))
            ->body(__('Are you sure you want to delete this comment?'))
            ->actions([
                Action::make('confirm')
                    ->label(__('Confirm'))
                    ->color('danger')
                    ->button()
                    ->close()
                    ->emit('doDeleteComment', compact('commentId')),
                Action::make('cancel')
                    ->label(__('Cancel'))
                    ->close()
            ])
            ->persistent()
            ->send();
    }

    public function doDeleteComment(int $commentId): void
    {
        TicketComment::where('id', $commentId)->delete();
        $this->record->refresh();
        $this->notify('success', __('Comment deleted'));
    }

    public function cancelEditComment(): void
    {
        $this->form->fill();
        $this->selectedCommentId = null;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle CC users
        if (isset($data['cc_users'])) {
            $ccUsers = $data['cc_users'];
            unset($data['cc_users']);

            // We'll attach CC users after the ticket is saved
            $this->ccUsers = $ccUsers;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Attach CC users if provided
        if (isset($this->ccUsers)) {
            $this->record->ccUsers()->sync($this->ccUsers);
        }
    }

    // Method to get users for JavaScript injection
    public function getMentionUsersJs(): string
    {
        $users = $this->getAvailableUsers();
        return json_encode($users, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Fixed method to get available users
    private function getAvailableUsers(): array
    {
        try {
            // Get users from project
            $projectUsers = collect();
            if ($this->record && $this->record->project) {
                $projectUsers = $this->record->project->users ?? collect();
            }

            // Get watchers (project owner, ticket owner, responsible)
            $watcherIds = [];
            if ($this->record) {
                if ($this->record->owner_id) {
                    $watcherIds[] = $this->record->owner_id;
                }
                if ($this->record->responsible_id) {
                    $watcherIds[] = $this->record->responsible_id;
                }
                if ($this->record->project && $this->record->project->owner_id) {
                    $watcherIds[] = $this->record->project->owner_id;
                }
            }

            // Get additional users by IDs
            $watcherUsers = collect();
            if (!empty($watcherIds)) {
                $watcherUsers = User::whereIn('id', array_unique($watcherIds))->get();
            }

            // Merge and get unique users
            $users = $projectUsers->merge($watcherUsers)->unique('id');

            // If no users found, get all users as fallback
            if ($users->isEmpty()) {
                $users = User::limit(50)->get(); // Limit to prevent performance issues
            }

            return $users->map(function ($user) {
                // Generate username from email if not exists
                $username = $this->getUserUsername($user);

                return [
                    'id' => $user->id,
                    'username' => $username,
                    'name' => $user->name ?? 'Unknown User',
                    'avatar' => $this->getDefaultAvatar($user)
                ];
            })->filter(function($user) {
                // Filter out users without username
                return !empty($user['username']) && !empty($user['name']);
            })->values()->toArray();

        } catch (\Exception $e) {
            \Log::error('Error getting available users for mentions: ' . $e->getMessage());
            return [];
        }
    }

    // Helper method to get username
    private function getUserUsername($user): string
    {
        // If user has username field, use it
        if (isset($user->username) && !empty($user->username)) {
            return $user->username;
        }

        // If user has oidc_username, use it
        if (isset($user->oidc_username) && !empty($user->oidc_username)) {
            return $user->oidc_username;
        }

        // Generate from email
        if (!empty($user->email)) {
            $baseUsername = strtolower(explode('@', $user->email)[0]);
            return preg_replace('/[^a-z0-9_]/', '', $baseUsername);
        }

        // Fallback to user ID
        return 'user' . $user->id;
    }

    // Simple avatar method that doesn't rely on external methods
    private function getDefaultAvatar($user): string
    {
        // Create a simple gravatar URL or use default
        if (!empty($user->email)) {
            $hash = md5(strtolower(trim($user->email)));
            return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=40";
        }

        // Use UI Avatars as fallback
        $name = urlencode($user->name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&size=40&background=random";
    }

    // Method untuk mendapatkan comments dengan formatted content
    public function getFormattedComments()
    {
        return $this->record->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'user' => $comment->user,
                'content' => $this->formatMentions($comment->content),
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ];
        });
    }

    // Method untuk format mentions dalam content
    private function formatMentions($content)
    {
        return preg_replace_callback(
            '/@([a-zA-Z0-9_]+)/',
            function ($matches) {
                $username = $matches[1];

                // Cari user berdasarkan username
                $user = User::where('username', $username)->first();

                if ($user) {
                    return sprintf(
                        '<span class="mention-tag" data-user-id="%d" style="
                            background-color: #dbeafe;
                            color: #1e40af;
                            padding: 2px 6px;
                            border-radius: 12px;
                            font-size: 0.875rem;
                            display: inline-block;
                            margin: 0 2px;
                            text-decoration: none;
                            border: 1px solid #93c5fd;
                            cursor: pointer;
                        ">@%s</span>',
                        $user->id,
                        htmlspecialchars($username)
                    );
                }

                // Jika user tidak ditemukan, kembalikan mention biasa
                return '@' . htmlspecialchars($username);
            },
            $content
        );
    }
}