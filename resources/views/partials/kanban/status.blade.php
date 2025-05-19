<div class="kanban-statuses">
    <!-- Column Header -->
    <div class="status-header" style="border-color: {{ $status['color'] }};">
        <div class="flex items-center justify-between">
            <span>{{ $status['title'] }}</span>
            @if($status['size'] > 0)
            <span class="px-2 py-1 text-xs rounded-full"
                style="background-color: {{ $status['color'] }}20; color: {{ $status['color'] }};">
                {{ $status['size'] }}
            </span>
            @endif
        </div>
    </div>

    <!-- Column Body -->
    <div class="status-container">
        <!-- Existing tickets container -->
        <div id="status-records-{{ $status['id'] }}" data-status="{{ $status['id'] }}"
            class="flex flex-col flex-1 gap-3">
            @foreach($this->getRecords()->where('status', $status['id']) as $record)
            @include('partials.kanban.record')
            @endforeach
        </div>

        <!-- Add a card button - Positioned at bottom -->
        @if($status['add_ticket'])
        <button wire:click="createTicketWithStatus({{ $status['id'] }})" wire:loading.attr="disabled"
            class="create-record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span wire:loading.remove wire:target="createTicketWithStatus({{ $status['id'] }})">
                {{ __('Add a card') }}
            </span>
            <span wire:loading wire:target="createTicketWithStatus({{ $status['id'] }})">
                {{ __('Adding...') }}
            </span>
        </button>
        @endif
    </div>

    <!-- Modal untuk create ticket dengan status spesifik -->
    @if($ticket && ($selectedStatusId ?? null) == $status['id'])
    <div class="dialog-container">
        <div class="dialog dialog-xl">
            <div class="dialog-header">
                <div class="flex items-center gap-2">
                    <span>{{ __('Create ticket for') }}</span>
                    <span class="px-2 py-1 text-sm font-medium rounded"
                        style="background-color: {{ $status['color'] }}20; color: {{ $status['color'] }};">
                        {{ $status['title'] }}
                    </span>
                </div>
            </div>
            <div class="dialog-content">
                @livewire('road-map.issue-form', [
                'project' => $project ?? null,
                'defaultStatusId' => $status['id']
                ], key('issue-form-status-'.$status['id']))
            </div>
        </div>
    </div>
    @endif
</div>