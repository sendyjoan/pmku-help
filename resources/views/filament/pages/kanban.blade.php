<x-filament::page>
    @livewire('kanban.ticket-modal', key('ticket-modal'))
    <div class="w-full mx-auto" wire:ignore>
        <details class="w-full duration-300 bg-white open:bg-gray-200">
            <summary class="relative w-full px-5 py-3 text-base text-gray-500 cursor-pointer bg-inherit">
                {{ __('Filters') }}
            </summary>
            <div class="px-5 py-3 bg-white">
                <form>
                    {{ $this->form }}
                </form>
            </div>
        </details>
    </div>

    <div class="kanban-container">

        @foreach($this->getStatuses() as $status)
        @include('partials.kanban.status')
        @endforeach

    </div>

    @push('scripts')
    <script src="{{ asset('js/Sortable.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for Livewire to be ready
            document.addEventListener('livewire:load', function () {
                let record;
                @foreach($this->getStatuses() as $status)
                    record = document.querySelector('#status-records-{{ $status['id'] }}');

                    if (record) {
                        Sortable.create(record, {
                            group: {
                                name: 'status-{{ $status['id'] }}',
                                pull: true,
                                put: true
                            },
                            handle: '.handle',
                            animation: 100,
                            onEnd: function (evt) {
                                // Use Livewire.emit instead of window.livewire
                                Livewire.emit('recordUpdated',
                                    +evt.clone.dataset.id,
                                    +evt.newIndex,
                                    +evt.to.dataset.status,
                                );
                            },
                        });
                    }
                @endforeach
            });

            // Global click handler for ticket cards
            document.addEventListener('click', function(e) {
                // Find the closest kanban record
                const ticketCard = e.target.closest('.kanban-record');

                if (ticketCard) {
                    // Don't trigger modal if clicking on handle, buttons, or links
                    if (e.target.closest('.handle') ||
                        e.target.closest('button') ||
                        e.target.closest('a') ||
                        e.target.tagName === 'A') {
                        return;
                    }

                    // Prevent default behavior
                    e.preventDefault();
                    e.stopPropagation();

                    // Get ticket ID and emit event
                    const ticketId = ticketCard.dataset.ticketId || ticketCard.dataset.id;
                    if (ticketId) {
                        // Use Livewire.emit to call the method
                        Livewire.emit('openTicketModal', parseInt(ticketId));
                    }
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    Livewire.emit('closeTicketModal');
                }
            });
        });
    </script>
    @endpush

</x-filament::page>