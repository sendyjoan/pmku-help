<div>
    {{-- Modal Overlay --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto modal-lg" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex justify-center min-h-screen px-4 pt-4 pb-20 text-center items-centre sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50" aria-hidden="true"
                wire:click="closeModal"></div>

            {{-- Modal panel - Much wider now --}}
            <div
                class="inline-block overflow-hidden text-left align-top transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                @if($ticket)

                {{-- Main content area --}}
                <div class="flex bg-gray-50 min-h-96">
                    {{-- Left column - Main content --}}
                    <div class="flex-1 p-6">
                        {{-- Title with status indicator --}}
                        <div class="mb-6">
                            <div class="flex items-center mb-2 space-x-2">
                                <span class="text-sm text-gray-600">{{ $ticket->code }}</span>
                                <span class="px-2 py-1 text-xs text-white rounded-full"
                                    style="background-color: {{ $ticket->status->color }}">
                                    {{ $ticket->status->name }}
                                </span>
                            </div>
                            <h1 class="mb-2 text-2xl font-semibold text-gray-900">{{ $ticket->name }}</h1>
                            <p class="text-sm text-gray-600">
                                in list <span class="font-medium">{{ $ticket->status->name }}</span>
                            </p>
                        </div>

                        {{-- Description --}}
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="flex items-center text-lg font-medium text-gray-900">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Description
                                </h2>
                            </div>
                            <div class="p-4 bg-white border border-gray-200 rounded-lg">
                                @if($ticket->content)
                                <div class="prose-sm prose text-gray-700 max-w-none">
                                    {!! $ticket->content !!}
                                </div>
                                @else
                                <p class="italic text-gray-500">No description provided...</p>
                                @endif
                            </div>
                        </div>

                        {{-- Attachments --}}
                        @if($ticket->media && $ticket->media->count() > 0)
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="flex items-center text-lg font-medium text-gray-900">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Attachments
                                </h2>
                                <button class="text-sm font-medium text-gray-500 hover:text-gray-700">Add</button>
                            </div>
                            <div class="space-y-3">
                                @foreach($ticket->media as $media)
                                <div
                                    class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex items-center justify-center w-16 h-12 bg-gray-100 rounded">
                                            @if(str_starts_with($media->mime_type, 'image/'))
                                            <img src="{{ $media->getUrl() }}" alt="{{ $media->name }}"
                                                class="object-cover w-full h-full rounded">
                                            @else
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $media->name }}</p>
                                            <p class="text-sm text-gray-500">
                                                Added {{ $media->created_at->format('M j, Y \a\t g:i A') }}
                                                • {{ $media->human_readable_size }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                <path fill-rule="evenodd"
                                                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Activity --}}
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="flex items-center text-lg font-medium text-gray-900">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Activity
                                    @if($ticket->activities->count() + $ticket->comments->count() > 0)
                                    <span class="ml-1 text-base">({{ $ticket->activities->count() +
                                        $ticket->comments->count() }})</span>
                                    @endif
                                </h2>
                                <button class="text-sm font-medium text-gray-500 hover:text-gray-700">Show
                                    details</button>
                            </div>

                            {{-- Activity feed --}}
                            <div class="space-y-4 overflow-y-auto max-h-80">
                                {{-- Combine activities and comments and sort by date --}}
                                @php
                                $activities = $ticket->activities->map(function($activity) {
                                return (object)[
                                'type' => 'activity',
                                'user' => $activity->user,
                                'created_at' => $activity->created_at,
                                'content' => "changed status from {$activity->oldStatus->name} to
                                {$activity->newStatus->name}",
                                ];
                                });

                                $comments = $ticket->comments->map(function($comment) {
                                return (object)[
                                'type' => 'comment',
                                'user' => $comment->user,
                                'created_at' => $comment->created_at,
                                'content' => $comment->content,
                                ];
                                });

                                $allActivity = $activities->concat($comments)->sortByDesc('created_at');
                                @endphp

                                @if($allActivity->count() > 0)
                                @foreach($allActivity as $item)
                                <div class="flex items-start space-x-3">
                                    <img class="w-8 h-8 rounded-full" src="{{ $item->user->avatar_url }}"
                                        alt="{{ $item->user->name }}">
                                    <div class="flex-1">
                                        <div class="p-3 bg-white border border-gray-200 rounded-lg">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-medium text-gray-900">{{ $item->user->name }}</span>
                                                <span class="text-sm text-gray-500">{{
                                                    $item->created_at->diffForHumans() }}</span>
                                            </div>
                                            @if($item->type === 'comment')
                                            <div class="text-gray-700">
                                                {!! $item->content !!}
                                            </div>
                                            @else
                                            <div class="text-sm text-gray-600">
                                                {{ $item->content }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @else
                                <div class="py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <p>No activity yet. Add a comment to get the conversation started!</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right sidebar - Actions & Info --}}
                    <div class="p-6 bg-white border-l border-gray-200 w-80">

                        {{-- Ticket info --}}
                        <div class="p-4 mb-6 rounded-lg bg-gray-50">
                            <h3 class="mb-3 text-sm font-medium text-gray-900">Ticket Details</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Project:</span>
                                    <span class="font-medium">{{ $ticket->project->name }}</span>
                                </div>
                                @if($ticket->epic)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Epic:</span>
                                    <span class="font-medium">{{ $ticket->epic->name }}</span>
                                </div>
                                @endif
                                @if($ticket->sprint)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Sprint:</span>
                                    <span class="font-medium">{{ $ticket->sprint->name }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Owner:</span>
                                    <div class="flex items-center space-x-2">
                                        <img class="w-5 h-5 rounded-full" src="{{ $ticket->owner->avatar_url }}"
                                            alt="{{ $ticket->owner->name }}">
                                        <span class="font-medium">{{ $ticket->owner->name }}</span>
                                    </div>
                                </div>
                                @if($ticket->responsible)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Assignee:</span>
                                    <div class="flex items-center space-x-2">
                                        <img class="w-5 h-5 rounded-full" src="{{ $ticket->responsible->avatar_url }}"
                                            alt="{{ $ticket->responsible->name }}">
                                        <span class="font-medium">{{ $ticket->responsible->name }}</span>
                                    </div>
                                </div>
                                @endif
                                @if($ticket->due_date)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Due Date:</span>
                                    <span class="font-medium {{ $ticket->due_date->isPast() ? 'text-red-600' : '' }}">
                                        {{ $ticket->due_date->format('M j, Y') }}
                                        @if($ticket->due_date->isPast())
                                        (Overdue)
                                        @endif
                                    </span>
                                </div>
                                @endif
                                @if($ticket->estimation)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Estimation:</span>
                                    <span class="font-medium">{{ $ticket->estimationForHumans }}</span>
                                </div>
                                @endif
                                @if($ticket->hours->count() > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Time Logged:</span>
                                    <span class="font-medium">{{ $ticket->totalLoggedHours }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Priority:</span>
                                    <div class="flex items-center space-x-2">
                                        <span class="w-3 h-3 rounded-full"
                                            style="background-color: {{ $ticket->priority->color }}"></span>
                                        <span class="font-medium">{{ $ticket->priority->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mb-6">
                            <h3 class="mb-3 text-sm font-medium tracking-wider text-gray-500 uppercase">Actions</h3>
                            <div class="space-y-2">
                                <a href="{{ route('filament.resources.tickets.view', $ticket) }}" target="_blank"
                                    class="flex items-center w-full px-3 py-2 text-left text-gray-700 transition-colors rounded-lg hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                        <path fill-rule="evenodd"
                                            d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Open Full View
                                </a>
                                <a href="{{ route('filament.resources.tickets.edit', $ticket) }}" target="_blank"
                                    class="flex items-center w-full px-3 py-2 text-left text-gray-700 transition-colors rounded-lg hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z">
                                        </path>
                                    </svg>
                                    Edit Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>