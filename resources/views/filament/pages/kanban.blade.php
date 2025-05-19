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
    setTimeout(function() {
        initializeKanban();
    }, 1000);

    function initializeKanban() {
        // Try to find kanban component more specifically
        let kanbanComponent = null;

        // Method 1: Look for component by checking if it has kanban-related elements
        const kanbanContainer = document.querySelector('.kanban-container') ||
                              document.querySelector('[class*="kanban"]') ||
                              document.querySelector('[id*="status-records"]')?.closest('[wire\\:id]');

        if (kanbanContainer) {
            const componentElement = kanbanContainer.closest('[wire\\:id]') || kanbanContainer;
            if (componentElement && componentElement.hasAttribute('wire:id')) {
                const componentId = componentElement.getAttribute('wire:id');
                kanbanComponent = window.Livewire.find(componentId);
            }
        }

        // Method 2: If not found, look for page component (usually the main one)
        if (!kanbanComponent) {
            const pageComponent = document.querySelector('.filament-page [wire\\:id]') ||
                                document.querySelector('main [wire\\:id]') ||
                                document.querySelector('[x-data] [wire\\:id]');

            if (pageComponent) {
                const componentId = pageComponent.getAttribute('wire:id');
                const component = window.Livewire.find(componentId);

                // Check if this component has recordUpdated method by testing component name
                if (component && component.__instance) {
                    const componentName = component.__instance.name.toLowerCase();
                    if (componentName.includes('kanban') ||
                        componentName.includes('page') ||
                        componentName.includes('board')) {
                        kanbanComponent = component;
                    }
                }
            }
        }

        // Method 3: Last resort - find any component that's not notifications/modal
        if (!kanbanComponent) {
            const allComponents = document.querySelectorAll('[wire\\:id]');
            for (let element of allComponents) {
                const componentId = element.getAttribute('wire:id');
                const component = window.Livewire.find(componentId);

                if (component && component.__instance) {
                    const componentName = component.__instance.name.toLowerCase();
                    // Skip known non-kanban components
                    if (!componentName.includes('notification') &&
                        !componentName.includes('modal') &&
                        !componentName.includes('global-search')) {
                        kanbanComponent = component;
                        break;
                    }
                }
            }
        }

        if (!kanbanComponent) {
            console.error('Could not find kanban component');
            return;
        }

        // Initialize sortable for each status container
        const statusContainers = document.querySelectorAll('[id*="status-records"]');

        statusContainers.forEach(function(container) {
            // Destroy existing sortable
            if (container.sortable) {
                container.sortable.destroy();
            }

            // Create new sortable
            container.sortable = Sortable.create(container, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',

                onEnd: function(evt) {
                    const ticketId = parseInt(evt.item.dataset.id);
                    const newIndex = parseInt(evt.newIndex);
                    const newStatusId = parseInt(evt.to.dataset.status || evt.to.id.replace('status-records-', ''));
                    const oldStatusId = parseInt(evt.from.dataset.status || evt.from.id.replace('status-records-', ''));

                    // Only update if something actually changed
                    if (oldStatusId !== newStatusId || evt.oldIndex !== evt.newIndex) {
                        // Show loading state
                        evt.item.style.opacity = '0.7';

                        // Try recordUpdated first
                        kanbanComponent.call('recordUpdated', ticketId, newIndex, newStatusId)
                            .then(function() {
                                evt.item.style.opacity = '';
                            })
                            .catch(function(error) {
                                // Try alternative method names
                                const alternatives = ['updateTicketPosition', 'moveTicket', 'updateTicketStatus'];
                                let success = false;

                                for (let method of alternatives) {
                                    try {
                                        kanbanComponent.call(method, ticketId, newIndex, newStatusId)
                                            .then(function() {
                                                evt.item.style.opacity = '';
                                                success = true;
                                            })
                                            .catch(function() {});
                                        break;
                                    } catch (e) {}
                                }

                                if (!success) {
                                    evt.item.style.opacity = '';
                                    alert('Failed to update ticket position. Please refresh the page.');
                                }
                            });
                    }
                }
            });
        });

        // Setup modal handlers
        setupModalHandlers();
    }

    function setupModalHandlers() {
        document.addEventListener('click', function(e) {
            const ticketCard = e.target.closest('.kanban-record');

            if (!ticketCard) return;

            // Skip if clicking on buttons/links
            if (e.target.closest('button') || e.target.closest('a') || e.target.tagName === 'A') {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const ticketId = ticketCard.dataset.id || ticketCard.dataset.ticketId;

            if (ticketId) {
                // Open modal using emit (should work globally)
                window.Livewire.emit('openTicketModal', parseInt(ticketId));
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.Livewire.emit('closeTicketModal');
        }
    });

    // Re-initialize on Livewire updates
    document.addEventListener('livewire:load', initializeKanban);
    document.addEventListener('livewire:navigated', initializeKanban);
});
    </script>

    <style>
        /* Kanban drag and drop styles */
        .sortable-ghost {
            opacity: 0.4;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border: 2px dashed #9ca3af;
            border-radius: 8px;
            transform: rotate(2deg);
        }

        .sortable-chosen {
            opacity: 0.9;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transform: scale(1.02);
            z-index: 100;
        }

        .kanban-record {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .kanban-record:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .kanban-record .handle {
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: grab;
            color: #6b7280;
        }

        .kanban-record:hover .handle {
            opacity: 1;
        }

        .handle:active {
            cursor: grabbing;
        }

        /* Status container styling */
        .status-container {
            min-height: 100px;
            transition: background-color 0.3s ease;
            border-radius: 8px;
            padding: 8px;
        }

        .status-container:hover {
            background-color: rgba(249, 250, 251, 0.5);
        }

        /* Loading state for cards being updated */
        .kanban-record[style*="opacity: 0.7"] {
            pointer-events: none;
        }

        .kanban-record[style*="opacity: 0.7"]::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .kanban-record:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .sortable-chosen {
                transform: scale(1.01);
            }
        }
    </style>
    @endpush

</x-filament::page>
