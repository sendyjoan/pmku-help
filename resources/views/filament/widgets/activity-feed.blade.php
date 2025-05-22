<x-filament::widget>
    <x-filament::card>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('Recent Activity') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                            {{ $total_activities }} {{ __('status changes') }}
                        </span>
                        <span
                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                            {{ $total_comments }} {{ __('comments') }}
                        </span>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    {{ __('Last 15 activities') }}
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="space-y-3 overflow-y-auto max-h-96">
                @forelse($feed as $item)
                <div
                    class="flex items-start gap-3 p-3 transition-colors rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        <img src="{{ $item['user']->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($item['user']->name) }}"
                            alt="{{ $item['user']->name }}" class="object-cover w-8 h-8 rounded-full">
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <!-- Activity Type Icon -->
                            @if($item['type'] === 'activity')
                            <div class="flex items-center justify-center w-5 h-5 bg-blue-100 rounded-full">
                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            @else
                            <div class="flex items-center justify-center w-5 h-5 bg-green-100 rounded-full">
                                <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            @endif

                            <!-- User Name -->
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $item['user']->name }}
                            </span>

                            <!-- Action Description -->
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $item['data']['description'] }}
                            </span>

                            <!-- Time -->
                            <span class="ml-auto text-xs text-gray-500">
                                {{ $item['created_at']->diffForHumans() }}
                            </span>
                        </div>

                        <!-- Ticket Info -->
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-gray-500">
                                {{ $item['ticket']->project->name }}
                            </span>
                            <span class="text-xs text-gray-400">•</span>
                            <a href="{{ route('filament.resources.tickets.share', $item['ticket']->code) }}"
                                target="_blank"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $item['ticket']->code }}
                            </a>
                            <span class="text-xs text-gray-600 truncate">
                                {{ Str::limit($item['ticket']->name, 40) }}
                            </span>
                        </div>

                        <!-- Activity-specific content -->
                        @if($item['type'] === 'activity')
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex items-center px-2 py-1 text-white rounded-full"
                                style="background-color: {{ $item['data']['old_status']->color }}">
                                {{ $item['data']['old_status']->name }}
                            </span>
                            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="inline-flex items-center px-2 py-1 text-white rounded-full"
                                style="background-color: {{ $item['data']['new_status']->color }}">
                                {{ $item['data']['new_status']->name }}
                            </span>
                        </div>
                        @endif

                        @if($item['type'] === 'comment')
                        <div class="p-2 mt-2 bg-white border-green-500 rounded dark:bg-gray-900 border-l-3">
                            <div class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                                {!! Str::limit(strip_tags($item['data']['content']), 120) !!}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-8 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">{{ __('No recent activity') }}
                    </h3>
                    <p class="text-sm text-gray-500">{{ __('Activity will appear here when tickets are updated or
                        commented on.') }}</p>
                </div>
                @endforelse
            </div>

            @if($feed->count() > 0)
            <!-- Footer -->
            <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500">
                    {{ __('Showing recent activity from your tickets and projects') }}
                </div>
                <a href="{{ route('filament.resources.tickets.index') }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                    {{ __('View all tickets') }} →
                </a>
            </div>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>