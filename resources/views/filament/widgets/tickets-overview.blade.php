<x-filament::widget>
    <x-filament::card>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Tickets Overview') }}
                </h3>
                <div class="text-sm text-gray-500">
                    {{ __('Total: :count tickets', ['count' => $totalTickets]) }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Ticket Types Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ __('By Type') }}</h4>
                    </div>

                    <div class="space-y-3">
                        @forelse($ticketTypes as $type)
                        <div
                            class="flex items-center justify-between p-3 transition-colors rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg"
                                    style="background-color: {{ $type['color'] }}20;">
                                    @if($type['icon'])
                                    <x-icon name="{{ $type['icon'] }}" class="w-4 h-4"
                                        style="color: {{ $type['color'] }}" />
                                    @else
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $type['color'] }}">
                                    </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $type['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $type['percentage'] }}% of total
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $type['count'] }}
                                </div>
                                <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full transition-all duration-300"
                                        style="width: {{ $type['percentage'] }}%; background-color: {{ $type['color'] }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="py-6 text-center text-gray-500">
                            {{ __('No ticket types found') }}
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Ticket Priorities Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ __('By Priority') }}</h4>
                    </div>

                    <div class="space-y-3">
                        @forelse($ticketPriorities as $priority)
                        <div
                            class="flex items-center justify-between p-3 transition-colors rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg"
                                    style="background-color: {{ $priority['color'] }}20;">
                                    <div class="w-3 h-3 rounded-full"
                                        style="background-color: {{ $priority['color'] }}"></div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $priority['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $priority['percentage'] }}% of total
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $priority['count'] }}
                                </div>
                                <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full transition-all duration-300"
                                        style="width: {{ $priority['percentage'] }}%; background-color: {{ $priority['color'] }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="py-6 text-center text-gray-500">
                            {{ __('No ticket priorities found') }}
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 md:grid-cols-4 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $ticketTypes->count() }}</div>
                    <div class="text-xs tracking-wide text-gray-500 uppercase">{{ __('Types') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ $ticketPriorities->count() }}</div>
                    <div class="text-xs tracking-wide text-gray-500 uppercase">{{ __('Priorities') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $totalTickets }}</div>
                    <div class="text-xs tracking-wide text-gray-500 uppercase">{{ __('Total Tickets') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $ticketTypes->where('count', '>', 0)->count() + $ticketPriorities->where('count', '>',
                        0)->count() }}
                    </div>
                    <div class="text-xs tracking-wide text-gray-500 uppercase">{{ __('Active Categories') }}</div>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>