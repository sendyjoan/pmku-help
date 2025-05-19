<x-filament::page>
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
        (() => {
                let record;
                @foreach($this->getStatuses() as $status)
                    record = document.querySelector('#status-records-{{ $status['id'] }}');

                    Sortable.create(record, {
                        group: {
                            name: 'status-{{ $status['id'] }}',
                            pull: true,
                            put: true
                        },
                        handle: '.handle',
                        animation: 100,
                        onEnd: function (evt) {
                            Livewire.emit('recordUpdated',
                                +evt.clone.dataset.id, // id
                                +evt.newIndex, // newIndex
                                +evt.to.dataset.status, // newStatus
                            );
                        },
                    })
                @endforeach
            })();
    </script>
    @endpush

</x-filament::page>