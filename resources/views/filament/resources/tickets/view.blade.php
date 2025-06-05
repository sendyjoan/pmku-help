@php($record = $this->record)
<x-filament::page>

    <a href="{{ route('filament.pages.kanban/{project}', ['project' => $record->project->id]) }}"
        class="flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-4 h-4" /> {{ __('Back to kanban board') }}
    </a>

    <div class="flex flex-col w-full gap-5 md:flex-row">

        <x-filament::card class="flex flex-col w-full gap-5 md:w-2/3">
            <div class="flex flex-col w-full gap-0">
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1 text-sm font-medium text-primary-500">
                        <x-heroicon-o-ticket class="w-4 h-4" />
                        {{ $record->code }}
                    </span>
                    <span class="text-sm font-light text-gray-400">|</span>
                    <span class="flex items-center gap-1 text-sm text-gray-500">
                        {{ $record->project->name }}
                    </span>
                </div>
                <span class="text-xl text-gray-700">
                    {{ $record->name }}
                </span>
            </div>
            <div class="flex items-center w-full gap-2">
                <div class="flex items-center justify-center px-2 py-1 text-xs text-center text-white rounded"
                    style="background-color: {{ $record->status->color }};">
                    {{ $record->status->name }}
                </div>
                <div class="flex items-center justify-center px-2 py-1 text-xs text-center text-white rounded"
                    style="background-color: {{ $record->priority->color }};">
                    {{ $record->priority->name }}
                </div>
                <div class="flex items-center justify-center px-2 py-1 text-xs text-center text-white rounded"
                    style="background-color: {{ $record->type->color }};">
                    <x-icon class="h-3 text-white" name="{{ $record->type->icon }}" />
                    <span class="ml-2">
                        {{ $record->type->name }}
                    </span>
                </div>
            </div>

            {{-- 📝 OPTIMIZED DESCRIPTION SECTION --}}
            <div class="flex flex-col w-full gap-2">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Content') }}
                </span>
                <div
                    class="w-full p-4 overflow-hidden prose-sm prose border border-gray-200 rounded-lg max-w-none bg-gray-50">
                    <div class="leading-relaxed text-gray-700">
                        {!! $record->content !!}
                    </div>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card class="flex flex-col w-full md:w-1/3">
            <div class="flex flex-col w-full gap-1" wire:ignore>
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Owner') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    <x-user-avatar :user="$record->owner" />
                    {{ $record->owner->name }}
                </div>
            </div>

            <div class="flex flex-col w-full gap-1 pt-3" wire:ignore>
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Responsible') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    @if($record->responsible)
                    <x-user-avatar :user="$record->responsible" />
                    @endif
                    {{ $record->responsible?->name ?? '-' }}
                </div>
            </div>

            @if($record->project->type === 'scrum')
            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Sprint') }}
                </span>
                <div class="flex flex-col justify-center w-full gap-1 text-gray-500">
                    @if($record->sprint)
                    {{ $record->sprint->name }}
                    <span class="text-xs text-gray-400">
                        {{ __('Starts at:') }} {{ $record->sprint->starts_at->format(__('Y-m-d')) }} -
                        {{ __('Ends at:') }} {{ $record->sprint->ends_at->format(__('Y-m-d')) }}
                    </span>
                    @else
                    -
                    @endif
                </div>
            </div>
            @else
            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Epic') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    @if($record->epic)
                    {{ $record->epic->name }}
                    @else
                    -
                    @endif
                </div>
            </div>
            @endif

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Estimation') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    @if($record->estimation)
                    {{ $record->estimationForHumans }}
                    @else
                    -
                    @endif
                </div>
            </div>

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Due Date') }}
                </span>
                <div class="w-full">
                    @if($record->due_date)
                    @if($record->due_date->lt(now()))
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                        {{ $record->due_date->format('M d, Y') }} (OVERDUE)
                    </span>
                    @elseif($record->due_date->isToday())
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5 animate-pulse"></span>
                        {{ $record->due_date->format('M d, Y') }} (DUE TODAY!)
                    </span>
                    @elseif($record->due_date->diffInDays(now()) <= 3) <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1.5"></span>
                        {{ $record->due_date->format('M d, Y') }} ({{ $record->due_date->diffInDays(now()) }} days left)
                        </span>
                        @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                            {{ $record->due_date->format('M d, Y') }} ({{ $record->due_date->diffInDays(now()) }} days
                            left)
                        </span>
                        @endif
                        @else
                        <span class="text-gray-400">No due date set</span>
                        @endif
                </div>
            </div>

            @if($record->isCompleted && $record->completedAt)
            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Completed Date') }}
                </span>
                <div class="w-full">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                        {{ $record->completedAt->format('M d, Y \a\t g:i A') }}
                    </span>
                    <div class="mt-1 text-xs text-gray-500">
                        ({{ $record->completedAt->diffForHumans() }})
                    </div>
                </div>
            </div>
            @endif

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Total time logged') }}
                </span>
                @if($record->hours()->count())
                @if($record->estimation)
                <div class="flex justify-between mb-1">
                    <span
                        class="text-base font-medium text-{{ $record->estimationProgress > 100 ? 'danger' : 'primary' }}-700">
                        {{ $record->totalLoggedHours }}
                    </span>
                    <span
                        class="text-sm font-medium text-{{ $record->estimationProgress > 100 ? 'danger' : 'primary' }}-700">
                        {{ round($record->estimationProgress) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-{{ $record->estimationProgress > 100 ? 'danger' : 'primary' }}-600 h-2.5 rounded-full"
                        style="width: {{ $record->estimationProgress > 100 ? 100 : $record->estimationProgress }}%">
                    </div>
                </div>
                @else
                <div class="flex items-center w-full gap-1 text-gray-500">
                    {{ $record->totalLoggedHours }}
                </div>
                @endif
                @else
                -
                @endif
            </div>

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Subscribers') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    @if($record->subscribers->count())
                    @foreach($record->subscribers as $subscriber)
                    <x-user-avatar :user="$subscriber" />
                    @endforeach
                    @else
                    {{ '-' }}
                    @endif
                </div>
            </div>

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('CC Users') }}
                </span>
                <div class="flex items-center w-full gap-1 text-gray-500">
                    @if($record->ccUsers->count())
                    @foreach($record->ccUsers as $ccUser)
                    <x-user-avatar :user="$ccUser" />
                    @endforeach
                    @else
                    {{ '-' }}
                    @endif
                </div>
            </div>

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Creation date') }}
                </span>
                <div class="w-full text-gray-500">
                    {{ $record->created_at->format(__('Y-m-d g:i A')) }}
                    <span class="text-xs text-gray-400">
                        ({{ $record->created_at->diffForHumans() }})
                    </span>
                </div>
            </div>

            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Last update') }}
                </span>
                <div class="w-full text-gray-500">
                    {{ $record->updated_at->format(__('Y-m-d g:i A')) }}
                    <span class="text-xs text-gray-400">
                        ({{ $record->updated_at->diffForHumans() }})
                    </span>
                </div>
            </div>

            @if($record->relations->count())
            <div class="flex flex-col w-full gap-1 pt-3">
                <span class="text-sm font-medium text-gray-500">
                    {{ __('Ticket relations') }}
                </span>
                <div class="w-full text-gray-500">
                    @foreach($record->relations as $relation)
                    <div class="flex items-center w-full gap-1 text-xs">
                        <span
                            class="rounded px-2 py-1 text-white bg-{{ config('system.tickets.relations.colors.' . $relation->type) }}-600">
                            {{ __(config('system.tickets.relations.list.' . $relation->type)) }}
                        </span>
                        <a target="_blank" class="font-medium hover:underline"
                            href="{{ route('filament.resources.tickets.share', $relation->relation->code) }}">
                            {{ $relation->relation->code }}
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </x-filament::card>

    </div>

    <div class="flex flex-col w-full gap-5 md:flex-row">

        <x-filament::card class="flex flex-col w-full md:w-2/3">
            <div class="flex items-center w-full gap-2">
                <button wire:click="selectTab('comments')"
                    class="md:text-xl text-sm p-3 border-b-2 border-transparent hover:border-primary-500 flex items-center gap-1 @if($tab === 'comments') border-primary-500 text-primary-500 @else text-gray-700 @endif">
                    {{ __('Comments') }}
                </button>
                <button wire:click="selectTab('activities')"
                    class="md:text-xl text-sm p-3 border-b-2 border-transparent hover:border-primary-500 @if($tab === 'activities') border-primary-500 text-primary-500 @else text-gray-700 @endif">
                    {{ __('Activities') }}
                </button>
                <button wire:click="selectTab('time')"
                    class="md:text-xl text-sm p-3 border-b-2 border-transparent hover:border-primary-500 @if($tab === 'time') border-primary-500 text-primary-500 @else text-gray-700 @endif">
                    {{ __('Time logged') }}
                </button>
                <button wire:click="selectTab('attachments')"
                    class="md:text-xl text-sm p-3 border-b-2 border-transparent hover:border-primary-500 @if($tab === 'attachments') border-primary-500 text-primary-500 @else text-gray-700 @endif">
                    {{ __('Attachments') }}
                </button>
            </div>

            @if($tab === 'comments')
            <form wire:submit.prevent="submitComment" class="pb-5">
                {{ $this->form }}
                <button type="submit" class="px-3 py-2 mt-3 text-white rounded bg-primary-500 hover:bg-primary-600">
                    {{ __($selectedCommentId ? 'Edit comment' : 'Add comment') }}
                </button>
                @if($selectedCommentId)
                <button type="button" wire:click="cancelEditComment"
                    class="px-3 py-2 mt-3 text-white rounded bg-warning-500 hover:bg-warning-600">
                    {{ __('Cancel') }}
                </button>
                @endif
            </form>

            @foreach($record->comments->sortByDesc('created_at') as $comment)
            <div
                class="w-full flex flex-col gap-2 @if(!$loop->last) pb-5 mb-5 border-b border-gray-200 @endif ticket-comment">
                <div class="flex justify-between w-full">
                    <span class="flex items-center gap-1 text-sm text-gray-500">
                        <span class="flex items-center gap-1 font-medium">
                            <x-user-avatar :user="$comment->user" />
                            {{ $comment->user->name }}
                        </span>
                        <span class="px-2 text-gray-400">|</span>
                        {{ $comment->created_at->format('Y-m-d g:i A') }}
                        ({{ $comment->created_at->diffForHumans() }})
                    </span>
                    @if($this->isAdministrator() || $comment->user_id === auth()->user()->id)
                    <div class="flex items-center gap-2 actions">
                        <button type="button" wire:click="editComment({{ $comment->id }})"
                            class="text-xs text-primary-500 hover:text-primary-600 hover:underline">
                            {{ __('Edit') }}
                        </button>
                        <span class="text-gray-300">|</span>
                        <button type="button" wire:click="deleteComment({{ $comment->id }})"
                            class="text-xs text-danger-500 hover:text-danger-600 hover:underline">
                            {{ __('Delete') }}
                        </button>
                    </div>
                    @endif
                </div>
                <div class="w-full prose-sm prose max-w-none">
                    {!! $comment->content !!}
                </div>
            </div>
            @endforeach
            @endif

            @if($tab === 'activities')
            <div class="flex flex-col w-full pt-5">
                @if($record->activities->count())
                @foreach($record->activities->sortByDesc('created_at') as $activity)
                <div class="w-full flex flex-col gap-2 @if(!$loop->last) pb-5 mb-5 border-b border-gray-200 @endif">
                    <span class="flex items-center gap-1 text-sm text-gray-500">
                        <span class="flex items-center gap-1 font-medium">
                            <x-user-avatar :user="$activity->user" />
                            {{ $activity->user->name }}
                        </span>
                        <span class="px-2 text-gray-400">•</span>
                        {{ $activity->formattedDate }}
                        <span class="text-xs text-gray-400">
                            ({{ $activity->created_at->diffForHumans() }})
                        </span>
                    </span>
                    <div class="flex items-center w-full gap-3">
                        <span class="text-gray-400">{{ $activity->oldStatus->name }}</span>
                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                        <span style="color: {{ $activity->newStatus->color }}" class="font-medium">
                            {{ $activity->newStatus->name }}
                        </span>

                        @if($activity->newStatus->name === 'Completed')
                        <span
                            class="inline-flex items-center px-2 py-1 ml-2 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            Completed
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
                @else
                <span class="text-sm font-medium text-gray-400">
                    {{ __('No activities yet!') }}
                </span>
                @endif
            </div>
            @endif

            @if($tab === 'time')
            <livewire:timesheet.time-logged :ticket="$record" />
            @endif

            @if($tab === 'attachments')
            <livewire:ticket.attachments :ticket="$record" />
            @endif
        </x-filament::card>

        <div class="flex flex-col w-full md:w-1/3"></div>

    </div>

    @push('scripts')
    <script>
        window.addEventListener('shareTicket', (e) => {
            const text = e.detail.url;
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Unable to copy to clipboard', err);
            }
            document.body.removeChild(textArea);
            new Notification()
                .success()
                .title('{{ __('Url copied to clipboard') }}')
                .duration(6000)
                .send()
        });
    </script>
    <script>
        window.mentionUsers = {!! $this->getMentionUsersJs() !!};
        console.log('Mention users injected:', window.mentionUsers);
    </script>

    <script src="{{ asset('js/mentions.js') }}"></script>

    <style>
        .mentions-dropdown {
            font-family: inherit;
        }

        .mention-item:hover {
            background-color: #f3f4f6 !important;
        }

        .mention-item.selected {
            background-color: #f3f4f6 !important;
        }

        .mention {
            background-color: #e0f2fe;
            color: #0277bd;
            padding: 1px 4px;
            border-radius: 4px;
            font-weight: 500;
        }

        /* 🎨 OPTIMIZED DESCRIPTION STYLING */
        .prose {
            line-height: 1.6;
        }

        .prose p {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .prose h1,
        .prose h2,
        .prose h3,
        .prose h4,
        .prose h5,
        .prose h6 {
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .prose ul,
        .prose ol {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
        }

        .prose blockquote {
            margin: 0.5rem 0;
            padding-left: 1rem;
            border-left: 3px solid #e5e7eb;
            font-style: italic;
        }

        .prose pre {
            margin: 0.5rem 0;
            padding: 0.75rem;
            background-color: #f3f4f6;
            border-radius: 0.375rem;
            overflow-x: auto;
        }

        .prose code {
            background-color: #f3f4f6;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
        }
    </style>
    @endpush
</x-filament::page>

@push('scripts')
<script>
    window.addEventListener('shareTicket', (e) => {
        const text = e.detail.url;
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Unable to copy to clipboard', err);
        }
        document.body.removeChild(textArea);
        new Notification()
            .success()
            .title('{{ __('Url copied to clipboard') }}')
            .duration(6000)
            .send()
    });
</script>
@endpush
