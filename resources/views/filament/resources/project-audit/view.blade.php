<x-filament::page>
    <div class="space-y-6">
        {{-- Project Overview Card --}}
        <x-filament::card>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Project Overview</h2>
                <div class="text-sm text-gray-500">
                    Last updated: {{ now()->format('M j, Y g:i A') }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Total Tickets --}}
                <div class="p-4 rounded-lg bg-blue-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 25 20">
                                <path
                                    d="M12.8638 3.49613C12.6846 3.18891 12.3557 3 12 3s-.6846.18891-.8638.49613l-3.49998 6c-.18042.30929-.1817.69147-.00336 1.00197S8.14193 11 8.5 11h7c.3581 0 .6888-.1914.8671-.5019.1784-.3105.1771-.69268-.0033-1.00197l-3.5-6ZM4 13c-.55228 0-1 .4477-1 1v6c0 .5523.44772 1 1 1h6c.5523 0 1-.4477 1-1v-6c0-.5523-.4477-1-1-1H4Zm12.5-1c-2.4853 0-4.5 2.0147-4.5 4.5s2.0147 4.5 4.5 4.5 4.5-2.0147 4.5-4.5-2.0147-4.5-4.5-4.5Z" />
                            </svg>

                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Tickets</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $auditData['overview']['total_tickets']
                                ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Completed Tickets --}}
                <div class="p-4 rounded-lg bg-green-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Completed</p>
                            <p class="text-2xl font-semibold text-gray-900">{{
                                $auditData['overview']['completed_tickets'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Overdue Tickets --}}
                <div class="p-4 rounded-lg bg-red-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z"
                                    clip-rule="evenodd" />
                            </svg>

                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Overdue</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $auditData['overview']['overdue_tickets']
                                ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Health Score --}}
                <div class="p-4 rounded-lg bg-purple-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M11.644 3.066a1 1 0 0 1 .712 0l7 2.666A1 1 0 0 1 20 6.68a17.694 17.694 0 0 1-2.023 7.98 17.406 17.406 0 0 1-5.402 6.158 1 1 0 0 1-1.15 0 17.405 17.405 0 0 1-5.403-6.157A17.695 17.695 0 0 1 4 6.68a1 1 0 0 1 .644-.949l7-2.666Zm4.014 7.187a1 1 0 0 0-1.316-1.506l-3.296 2.884-.839-.838a1 1 0 0 0-1.414 1.414l1.5 1.5a1 1 0 0 0 1.366.046l4-3.5Z"
                                    clip-rule="evenodd" />
                            </svg>

                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Health Score</p>
                            <p class="text-2xl font-semibold text-gray-900">{{
                                number_format($auditData['overview']['health_score'] ?? 0, 1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::card>

        {{-- Workflow Analysis --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Status Distribution --}}
            <x-filament::card>
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Status Distribution</h3>
                @if(!empty($auditData['workflow_analysis']['status_distribution']))
                <div class="space-y-3">
                    @foreach($auditData['workflow_analysis']['status_distribution'] as $status)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 rounded-full"
                                style="background-color: {{ $status['color'] ?? '#6B7280' }}"></div>
                            <span class="text-sm font-medium text-gray-900">{{ $status['name'] }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">{{ $status['count'] }}</span>
                            <div class="w-20 h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-blue-500 rounded-full" style="width: {{ $status['percentage'] }}%">
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">{{ number_format($status['percentage'], 1) }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">No status distribution data available.</p>
                @endif
            </x-filament::card>

            {{-- Team Performance --}}
            <x-filament::card>
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Team Performance</h3>
                @if(!empty($auditData['team_performance']))
                <div class="space-y-3">
                    @foreach($auditData['team_performance'] as $member)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <div class="flex items-center space-x-3">
                            @if($member['avatar'])
                            <img class="w-8 h-8 rounded-full" src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}">
                            @else
                            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 rounded-full">
                                <span class="text-xs font-medium text-gray-600">{{ substr($member['name'], 0, 1)
                                    }}</span>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $member['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $member['assigned_tickets'] }} tickets assigned</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">{{ number_format($member['completion_rate'], 1)
                                }}%</p>
                            <p class="text-xs text-gray-500">completion rate</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">No team performance data available.</p>
                @endif
            </x-filament::card>
        </div>

        {{-- Completion Analysis --}}
        <x-filament::card>
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Completion Analysis</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="p-4 text-center rounded-lg bg-green-50">
                    <p class="text-2xl font-bold text-green-600">{{
                        $auditData['completion_analysis']['on_time_completions'] ?? 0 }}</p>
                    <p class="text-sm text-gray-600">On-time completions</p>
                </div>
                <div class="p-4 text-center rounded-lg bg-red-50">
                    <p class="text-2xl font-bold text-red-600">{{ $auditData['completion_analysis']['late_completions']
                        ?? 0 }}</p>
                    <p class="text-sm text-gray-600">Late completions</p>
                </div>
                <div class="p-4 text-center rounded-lg bg-blue-50">
                    <p class="text-2xl font-bold text-blue-600">{{
                        number_format($auditData['completion_analysis']['on_time_percentage'] ?? 0, 1) }}%</p>
                    <p class="text-sm text-gray-600">On-time percentage</p>
                </div>
            </div>
        </x-filament::card>

        {{-- Recommendations --}}
        @if(!empty($auditData['recommendations']))
        <x-filament::card>
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Recommendations</h3>
            <div class="space-y-3">
                @foreach($auditData['recommendations'] as $recommendation)
                <div class="p-4 border-l-4 border-yellow-400 bg-yellow-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-yellow-800">{{ $recommendation['title'] ??
                                'Recommendation' }}</h4>
                            <p class="mt-1 text-sm text-yellow-700">{{ $recommendation['description'] ?? $recommendation
                                }}</p>
                            @if(isset($recommendation['priority']))
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-2">
                                Priority: {{ ucfirst($recommendation['priority']) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-filament::card>
        @endif

        {{-- Overdue Analysis --}}
        @if(!empty($auditData['overdue_analysis']['by_priority']) ||
        !empty($auditData['overdue_analysis']['by_assignee']))
        <x-filament::card>
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Overdue Analysis</h3>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- By Priority --}}
                @if(!empty($auditData['overdue_analysis']['by_priority']))
                <div>
                    <h4 class="mb-3 font-medium text-gray-800 text-md">By Priority</h4>
                    <div class="space-y-2">
                        @foreach($auditData['overdue_analysis']['by_priority'] as $priority)
                        <div class="flex items-center justify-between p-2 rounded bg-gray-50">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full"
                                    style="background-color: {{ $priority['color'] ?? '#6B7280' }}"></div>
                                <span class="text-sm">{{ $priority['name'] }}</span>
                            </div>
                            <span class="text-sm font-medium">{{ $priority['count'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- By Assignee --}}
                @if(!empty($auditData['overdue_analysis']['by_assignee']))
                <div>
                    <h4 class="mb-3 font-medium text-gray-800 text-md">By Assignee</h4>
                    <div class="space-y-2">
                        @foreach($auditData['overdue_analysis']['by_assignee'] as $assignee)
                        <div class="flex items-center justify-between p-2 rounded bg-gray-50">
                            <div class="flex items-center space-x-2">
                                @if($assignee['avatar'])
                                <img class="w-5 h-5 rounded-full" src="{{ $assignee['avatar'] }}"
                                    alt="{{ $assignee['name'] }}">
                                @else
                                <div class="flex items-center justify-center w-5 h-5 bg-gray-300 rounded-full">
                                    <span class="text-xs font-medium text-gray-600">{{ substr($assignee['name'], 0, 1)
                                        }}</span>
                                </div>
                                @endif
                                <span class="text-sm">{{ $assignee['name'] }}</span>
                            </div>
                            <span class="text-sm font-medium">{{ $assignee['count'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-filament::card>
        @endif
    </div>
</x-filament::page>
