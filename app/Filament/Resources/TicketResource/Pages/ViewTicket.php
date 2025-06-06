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
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

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
                ->modalWidth('lg')
                ->modalHeading(__('Log worked time'))
                ->modalSubheading(__('Track the time you spent working on this ticket'))
                ->modalButton(__('Log Time'))
                ->visible(fn() => in_array(
                    auth()->user()->id,
                    [$this->record->owner_id, $this->record->responsible_id]
                ))
                ->form([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('hours')
                                ->label(__('Hours'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(23)
                                ->step(1)
                                ->suffix('h')
                                ->columnSpan(1),

                            TextInput::make('minutes')
                                ->label(__('Minutes'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(59)
                                ->step(15)
                                ->suffix('m')
                                ->columnSpan(1),
                        ]),

                    Placeholder::make('time_examples')
                        ->label('')
                        ->content(new HtmlString('
                            <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded-lg">
                                <strong>Quick examples:</strong><br>
                                <span class="inline-block mr-4">• 15 min = 0h 15m</span>
                                <span class="inline-block mr-4">• 30 min = 0h 30m</span>
                                <span class="inline-block mr-4">• 1.5 hrs = 1h 30m</span><br>
                                <span class="inline-block mr-4">• 2 hrs = 2h 0m</span>
                                <span class="inline-block mr-4">• 8 hrs = 8h 0m</span>
                            </div>
                        ')),

                    Select::make('activity_id')
                        ->label(__('Activity Type'))
                        ->helperText(__('What type of work did you perform?'))
                        ->placeholder(__('Select an activity...'))
                        ->searchable()
                        ->required()
                        ->options(function () {
                            return Activity::with('parent')
                                ->ordered()
                                ->get()
                                ->pluck('indented_name', 'id');
                        }),

                    Textarea::make('comment')
                        ->label(__('Work Description'))
                        ->helperText(__('Briefly describe what you worked on (optional)'))
                        ->placeholder(__('e.g., Fixed login bug, Updated user interface, Wrote unit tests...'))
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (Collection $records, array $data): void {
                    // Konversi hours + minutes ke decimal hours
                    $hours = (float) ($data['hours'] ?? 0);
                    $minutes = (float) ($data['minutes'] ?? 0);
                    $totalHours = $hours + ($minutes / 60);

                    // Validasi minimal 1 menit (0.0167 hours)
                    if ($totalHours < 0.0167) {
                        $this->notify('danger', __('Minimum time is 1 minute'));
                        return;
                    }

                    $comment = $data['comment'] ?? '';

                    TicketHour::create([
                        'ticket_id' => $this->record->id,
                        'activity_id' => $data['activity_id'],
                        'user_id' => auth()->user()->id,
                        'value' => $totalHours,
                        'comment' => $comment
                    ]);

                    $this->record->refresh();

                    // Format pesan sukses yang lebih informatif
                    $timeFormatted = $hours > 0
                        ? ($minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h")
                        : "{$minutes}m";

                    $this->notify('success', __('Successfully logged :time for this ticket', ['time' => $timeFormatted]));
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
            // Editing existing comment
            $comment = TicketComment::find($this->selectedCommentId);

            if (!$comment) {
                $this->notify('danger', __('Comment not found'));
                return;
            }

            if (!$this->canEditComment($comment)) {
                $this->notify('danger', __('You do not have permission to edit this comment'));
                return;
            }

            $comment->update([
                'content' => $data['comment']
            ]);

            $this->notify('success', __('Comment updated successfully'));
        } else {
            // Creating new comment
            if (!$this->canCreateComment()) {
                $this->notify('danger', __('You do not have permission to create comments'));
                return;
            }

            TicketComment::create([
                'user_id' => auth()->user()->id,
                'ticket_id' => $this->record->id,
                'content' => $data['comment']
            ]);

            $this->notify('success', __('Comment created successfully'));
        }

        $this->record->refresh();
        $this->cancelEditComment();
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

    /**
     * Update editComment method with permission check
     */
    public function editComment(int $commentId): void
    {
        $comment = TicketComment::find($commentId);

        if (!$comment) {
            $this->notify('danger', __('Comment not found'));
            return;
        }

        if (!$this->canEditComment($comment)) {
            $this->notify('danger', __('You do not have permission to edit this comment'));
            return;
        }

        $this->form->fill([
            'comment' => $comment->content
        ]);
        $this->selectedCommentId = $commentId;
    }

    /**
     * Update deleteComment method with permission check
     */
    public function deleteComment(int $commentId): void
    {
        $comment = TicketComment::find($commentId);

        if (!$comment) {
            $this->notify('danger', __('Comment not found'));
            return;
        }

        if (!$this->canDeleteComment($comment)) {
            $this->notify('danger', __('You do not have permission to delete this comment'));
            return;
        }

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

    /**
     * Update doDeleteComment method with permission check
     */
    public function doDeleteComment(int $commentId): void
    {
        $comment = TicketComment::find($commentId);

        if (!$comment) {
            $this->notify('danger', __('Comment not found'));
            return;
        }

        if (!$this->canDeleteComment($comment)) {
            $this->notify('danger', __('You do not have permission to delete this comment'));
            return;
        }

        $comment->delete();
        $this->record->refresh();
        $this->notify('success', __('Comment deleted successfully'));
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
        /**
     * Check if user can view comments tab
     */
    public function canViewComments(): bool
    {
        return auth()->user()->can('List comments');
    }

    /**
     * Check if user can create comments
     */
    public function canCreateComment(): bool
    {
        return auth()->user()->can('Create comment');
    }

    /**
     * Check if user can edit a specific comment
     */
    public function canEditComment(TicketComment $comment): bool
    {
        // User must have Update comment permission AND be the owner of the comment
        return auth()->user()->can('Update comment') && $comment->user_id === auth()->user()->id;
    }

    /**
     * Check if user can delete a specific comment
     */
    public function canDeleteComment(TicketComment $comment): bool
    {
        // User must have Delete comment permission AND be the owner of the comment
        return auth()->user()->can('Delete comment') && $comment->user_id === auth()->user()->id;
    }

    /**
     * Update the selectTab method to check permissions
     */
    public function selectTab(string $tab): void
    {
        // Check if user can access comments tab
        if ($tab === 'comments' && !$this->canViewComments()) {
            $this->notify('warning', __('You do not have permission to view comments'));
            return;
        }

        $this->tab = $tab;
    }
}
