<x-filament::page>
    <div class="space-y-6">
        {{-- Form Section --}}
        <x-filament::card>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Export Settings</h3>
                    <p class="text-sm text-gray-600">Select date range for your timesheet export</p>
                </div>

                <form wire:submit.prevent="preview" class="space-y-4">
                    {{ $this->form }}

                    <div class="flex items-center space-x-3">
                        <x-filament::button type="submit" wire:loading.attr="disabled" icon="heroicon-o-eye">
                            <span wire:loading.remove wire:target="preview">{{ __('Preview Data') }}</span>
                            <span wire:loading wire:target="preview">{{ __('Loading...') }}</span>
                        </x-filament::button>

                        @if($showPreview)
                        <x-filament::button color="secondary" wire:click="resetPreview" icon="heroicon-o-x">
                            {{ __('Hide Preview') }}
                        </x-filament::button>
                        @endif
                    </div>
                </form>
            </div>
        </x-filament::card>

        {{-- Summary Section --}}
        @if($showPreview)
        <x-filament::card>
            <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-medium text-blue-900">Export Summary</h4>
                        <p class="text-sm text-blue-700">
                            Data from {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }}
                            to {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-900">{{ $totalRecords }}</div>
                        <div class="text-sm text-blue-700">Total Records</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-3">
                    <div class="p-3 bg-white border border-blue-200 rounded-lg">
                        <div class="text-lg font-semibold text-gray-900">{{ $totalHours }}</div>
                        <div class="text-sm text-gray-600">Total Hours Logged</div>
                    </div>

                    <div class="p-3 bg-white border border-blue-200 rounded-lg">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ $totalRecords > 0 ? round($totalHours / $totalRecords, 2) : 0 }}
                        </div>
                        <div class="text-sm text-gray-600">Average Hours per Entry</div>
                    </div>

                    <div class="p-3 bg-white border border-blue-200 rounded-lg">
                        <div class="text-lg font-semibold text-gray-900">
                            {{ \Carbon\Carbon::parse($start_date)->diffInDays(\Carbon\Carbon::parse($end_date)) + 1 }}
                        </div>
                        <div class="text-sm text-gray-600">Days in Range</div>
                    </div>
                </div>
            </div>
        </x-filament::card>
        @endif

        {{-- Preview Table Section --}}
        @if($showPreview)
        <x-filament::card>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Data Preview</h3>
                        <p class="text-sm text-gray-600">Review your timesheet data before exporting</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <x-filament::button color="success" wire:click="create" icon="heroicon-o-document-download">
                            {{ __('Export CSV') }}
                        </x-filament::button>

                        <x-filament::button color="success" wire:click="exportExcel"
                            icon="heroicon-o-document-download">
                            {{ __('Export Excel') }}
                        </x-filament::button>
                    </div>
                </div>

                @if($totalRecords > 0)
                <div class="overflow-hidden">
                    {{ $this->table }}
                </div>
                @else
                <div class="py-8 text-center">
                    <div class="flex items-center justify-center w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Data Found</h3>
                    <p class="mb-4 text-gray-600">
                        No timesheet entries found for the selected date range.<br>
                        Try adjusting your date range or check if you have logged any time.
                    </p>
                    <x-filament::button color="secondary" wire:click="resetPreview">
                        {{ __('Change Date Range') }}
                    </x-filament::button>
                </div>
                @endif
            </div>
        </x-filament::card>
        @endif

        {{-- Quick Actions Section --}}
        @if(!$showPreview)
        <x-filament::card>
            <div class="p-4 rounded-lg bg-gray-50">
                <h4 class="mb-3 font-medium text-gray-900 text-md">Quick Export Options</h4>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <button
                        wire:click="$set('start_date', '{{ now()->startOfWeek()->format('Y-m-d') }}'); $set('end_date', '{{ now()->endOfWeek()->format('Y-m-d') }}'); preview()"
                        class="p-3 text-left transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="font-medium text-gray-900">This Week</div>
                        <div class="text-sm text-gray-600">
                            {{ now()->startOfWeek()->format('M d') }} - {{ now()->endOfWeek()->format('M d, Y') }}
                        </div>
                    </button>

                    <button
                        wire:click="$set('start_date', '{{ now()->startOfMonth()->format('Y-m-d') }}'); $set('end_date', '{{ now()->endOfMonth()->format('Y-m-d') }}'); preview()"
                        class="p-3 text-left transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="font-medium text-gray-900">This Month</div>
                        <div class="text-sm text-gray-600">
                            {{ now()->startOfMonth()->format('M d') }} - {{ now()->endOfMonth()->format('M d, Y') }}
                        </div>
                    </button>

                    <button
                        wire:click="$set('start_date', '{{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}'); $set('end_date', '{{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}'); preview()"
                        class="p-3 text-left transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="font-medium text-gray-900">Last Month</div>
                        <div class="text-sm text-gray-600">
                            {{ now()->subMonth()->startOfMonth()->format('M d') }} - {{
                            now()->subMonth()->endOfMonth()->format('M d, Y') }}
                        </div>
                    </button>
                </div>
            </div>
        </x-filament::card>
        @endif
    </div>

    @push('scripts')
    <script>
        // Auto-update form when dates change via quick actions
        document.addEventListener('livewire:load', function () {
            Livewire.on('dateChanged', () => {
                // Trigger form update if needed
            });
        });
    </script>
    @endpush
</x-filament::page>
