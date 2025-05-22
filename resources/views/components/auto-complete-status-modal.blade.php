{{-- resources/views/components/auto-complete-status-modal.blade.php --}}
<div class="space-y-6">
    {{-- Project Info --}}
    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
        <h3 class="mb-2 text-lg font-medium text-gray-900">{{ $project->name }}</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">Auto Complete:</span>
                <span
                    class="ml-2 px-2 py-1 text-xs rounded-full {{ $project->auto_complete_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $project->auto_complete_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Days Threshold:</span>
                <span class="ml-2">{{ $project->auto_complete_days }} days</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Monitor Status:</span>
                <span class="px-2 py-1 ml-2 text-xs rounded-full"
                    style="background-color: {{ $fromStatus?->color ?? '#gray' }}20; color: {{ $fromStatus?->color ?? '#gray' }};">
                    {{ $project->auto_complete_from_status ?? 'Not set' }}
                </span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Target Status:</span>
                <span class="px-2 py-1 ml-2 text-xs rounded-full"
                    style="background-color: {{ $toStatus?->color ?? '#gray' }}20; color: {{ $toStatus?->color ?? '#gray' }};">
                    {{ $project->auto_complete_to_status ?? 'Not set' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Configuration Status --}}
    @if(!$project->auto_complete_enabled)
    <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium text-yellow-800">Auto Complete is currently disabled for this project.</span>
        </div>
    </div>
    @elseif(!$fromStatus || !$toStatus)
    <div class="p-4 border border-red-200 rounded-lg bg-red-50">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium text-red-800">Invalid status configuration. Please check the monitor and target
                statuses.</span>
        </div>
    </div>
    @endif

    {{-- Eligible Tickets --}}
    @if($project->auto_complete_enabled && $fromStatus && $toStatus)
    <div>
        <h4 class="flex items-center mb-3 font-medium text-gray-900 text-md">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
            Tickets Eligible for Auto Completion
            @if($eligibleTickets->count() > 0)
            <span class="px-2 py-1 ml-2 text-xs text-orange-800 bg-orange-100 rounded-full">
                {{ $eligibleTickets->count() }} tickets
            </span>
            @endif
        </h4>

        @if($eligibleTickets->count() > 0)
        <div class="overflow-hidden border border-gray-200 rounded-lg">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <div class="grid grid-cols-4 gap-4 text-xs font-medium tracking-wider text-gray-600 uppercase">
                    <div>Ticket</div>
                    <div>Title</div>
                    <div>Days in {{ $fromStatus->name }}</div>
                    <div>Owner</div>
                </div>
            </div>
            <div class="overflow-y-auto max-h-64">
                @foreach($eligibleTickets as $ticket)
                @php
                $lastActivity = $ticket->activities()
                ->where('new_status_id', $fromStatus->id)
                ->orderBy('created_at', 'desc')
                ->first();
                $daysInStatus = $lastActivity ? now()->diffInDays($lastActivity->created_at) : 0;
                @endphp
                <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50">
                    <div class="grid items-center grid-cols-4 gap-4">
                        <div>
                            <span class="text-sm font-medium text-blue-600">{{ $ticket->code }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-900">{{ Str::limit($ticket->name, 40) }}</span>
                        </div>
                        <div>
                            <span
                                class="text-sm {{ $daysInStatus >= $project->auto_complete_days ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                {{ $daysInStatus }} days
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($ticket->owner->avatar_url)
                            <img class="w-6 h-6 rounded-full" src="{{ $ticket->owner->avatar_url }}"
                                alt="{{ $ticket->owner->name }}">
                            @endif
                            <span class="text-sm text-gray-600">{{ $ticket->owner->name }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="p-3 mt-4 border border-blue-200 rounded-lg bg-blue-50">
            <p class="text-sm text-blue-800">
                <strong>Note:</strong> The auto-complete process runs daily via scheduled command.
                Tickets shown above will be automatically moved to "{{ $toStatus->name }}" status
                when they exceed {{ $project->auto_complete_days }} days in "{{ $fromStatus->name }}" status.
            </p>
        </div>
        @else
        <div class="py-8 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
            </svg>
            <p class="text-gray-500">No tickets are currently eligible for auto-completion.</p>
            <p class="mt-1 text-sm text-gray-400">All tickets in "{{ $fromStatus->name }}" status are within the {{
                $project->auto_complete_days }}-day threshold.</p>
        </div>
        @endif
    </div>
    @endif
</div>