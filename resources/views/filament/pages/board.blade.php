<x-filament::page>
    <div class="space-y-8">
        {{-- Projects Grid --}}
        @if($projects->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($projects as $project)
            <div wire:click="selectProject({{ $project->id }})"
                class="overflow-hidden transition-all duration-200 bg-white border border-gray-200 rounded-lg shadow-sm cursor-pointer group hover:shadow-md hover:border-blue-300">

                {{-- Project Header/Cover --}}
                <div class="relative h-32 overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600">
                    @if($project->cover)
                    <img src="{{ $project->cover }}" alt="{{ $project->name }}" class="object-cover w-full h-full">
                    @endif
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>

                    {{-- Project Type Badge --}}
                    <div class="absolute top-3 right-3">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-90 text-gray-800">
                            {{ ucfirst($project->type ?? 'kanban') }}
                        </span>
                    </div>

                    {{-- Status Indicator --}}
                    @if($project->status)
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white rounded-full"
                            style="background-color: {{ $project->status->color }}20; backdrop-filter: blur(4px);">
                            <span class="w-2 h-2 rounded-full mr-1.5"
                                style="background-color: {{ $project->status->color }}"></span>
                            {{ $project->status->name }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Project Content --}}
                <div class="p-5">
                    {{-- Project Name --}}
                    <h3 class="text-lg font-semibold text-gray-900 transition-colors group-hover:text-blue-600">
                        {{ $project->name }}
                    </h3>

                    {{-- Project Description --}}
                    @if($project->description)
                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                        {{ Str::limit(strip_tags($project->description), 100) }}
                    </p>
                    @endif

                    {{-- Project Meta --}}
                    <div class="flex items-center justify-between mt-4">
                        {{-- Owner --}}
                        <div class="flex items-center space-x-2">
                            <img class="w-6 h-6 border border-gray-200 rounded-full"
                                src="{{ $project->owner->avatar_url }}" alt="{{ $project->owner->name }}">
                            <span class="text-xs text-gray-500">{{ $project->owner->name }}</span>
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                            {{-- Tickets Count --}}
                            @if($project->tickets_count ?? $project->tickets->count())
                            <div class="flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ $project->tickets_count ?? $project->tickets->count() }} tickets</span>
                            </div>
                            @endif

                            {{-- Members Count --}}
                            @if($project->users->count() > 0)
                            <div class="flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z">
                                    </path>
                                </svg>
                                <span>{{ $project->users->count() + 1 }} members</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Project Actions Hint --}}
                    <div class="pt-3 mt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Click to open board</span>
                            <div
                                class="flex items-center space-x-1 text-gray-400 transition-colors group-hover:text-blue-600">
                                <span class="text-xs">{{ ucfirst($project->type ?? 'kanban') }} Board</span>
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Info Section --}}
        <div class="text-center">
            <div class="inline-flex items-center px-4 py-2 space-x-2 text-sm text-gray-500 rounded-lg bg-gray-50">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"></path>
                </svg>
                <span>Select a project to access its {{ __('Scrum') }} or {{ __('Kanban') }} board</span>
            </div>
        </div>
        @else
        {{-- Empty State --}}
        <div class="py-12 text-center">
            <div class="flex items-center justify-center w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No projects found</h3>
            <p class="mb-6 text-gray-500">You don't have access to any projects yet.</p>
            <a href="{{ route('filament.resources.projects.create') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd"></path>
                </svg>
                Create New Project
            </a>
        </div>
        @endif
    </div>
</x-filament::page>