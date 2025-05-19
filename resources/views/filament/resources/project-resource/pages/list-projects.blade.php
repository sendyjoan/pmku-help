<x-filament::page>
    @if($this->viewType === 'cards')
    {{-- Card View --}}
    <div class="space-y-6">
        {{-- Projects Grid --}}
        @php
        $projects = $this->getProjects();
        @endphp

        @if($projects->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($projects as $project)
            <div
                class="overflow-hidden transition-all duration-200 bg-white border border-gray-200 rounded-lg shadow-sm group hover:shadow-md hover:border-blue-300">

                {{-- Project Header/Cover --}}
                <div class="relative h-32 overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600">
                    @if($project->cover)
                    <img src="{{ $project->cover }}" alt="{{ $project->name }}" class="object-cover w-full h-full">
                    @endif

                    {{-- Favorite Button --}}
                    <button wire:click="toggleFavorite({{ $project->id }})"
                        class="absolute top-3 right-3 p-1.5 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
                        @if(auth()->user()->favoriteProjects()->where('projects.id', $project->id)->count())
                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                        @else
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                            </path>
                        </svg>
                        @endif
                    </button>

                    {{-- Project Type Badge --}}
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white rounded-full"
                            style="background-color: {{ $project->type === 'scrum' ? '#f59e0b' : '#6b7280' }}20; backdrop-filter: blur(4px);">
                            {{ ucfirst($project->type ?? 'kanban') }}
                        </span>
                    </div>

                    {{-- Status Badge --}}
                    <div class="absolute bottom-3 left-3">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white rounded-full"
                            style="background-color: {{ $project->status->color }}40; backdrop-filter: blur(4px);">
                            <span class="w-2 h-2 rounded-full mr-1.5"
                                style="background-color: {{ $project->status->color }}"></span>
                            {{ $project->status->name }}
                        </span>
                    </div>
                </div>

                {{-- Project Content --}}
                <div class="p-4">
                    {{-- Project Name --}}
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 transition-colors group-hover:text-blue-600">
                        {{ $project->name }}
                    </h3>

                    {{-- Project Description --}}
                    @if($project->description)
                    <p class="mb-3 text-sm text-gray-600 line-clamp-2">
                        {{ Str::limit(strip_tags($project->description), 100) }}
                    </p>
                    @endif

                    {{-- Project Meta --}}
                    <div class="flex items-center justify-between mb-4">
                        {{-- Owner --}}
                        <div class="flex items-center space-x-2">
                            <img class="w-6 h-6 border border-gray-200 rounded-full"
                                src="{{ $project->owner->avatar_url }}" alt="{{ $project->owner->name }}">
                            <span class="text-xs text-gray-500">{{ $project->owner->name }}</span>
                        </div>

                        {{-- Creation Date --}}
                        <span class="text-xs text-gray-400">
                            {{ $project->created_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Team Members --}}
                    @if($project->users && $project->users->count() > 0)
                    <div class="mb-4">
                        <div class="mb-2 text-xs text-gray-500">Team Members</div>
                        <div class="flex items-center space-x-1">
                            @foreach($project->users->take(4) as $user)
                            <img class="w-6 h-6 border border-white rounded-full shadow-sm"
                                src="{{ $user->avatar_url }}" alt="{{ $user->name }}" title="{{ $user->name }}">
                            @endforeach
                            @if($project->users->count() > 4)
                            <div
                                class="flex items-center justify-center w-6 h-6 bg-gray-100 border border-white rounded-full shadow-sm">
                                <span class="text-xs text-gray-600">+{{ $project->users->count() - 4 }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex items-center pt-3 space-x-2 border-t border-gray-100">
                        <button wire:click="openBoard({{ $project->id }})"
                            class="inline-flex items-center justify-center flex-1 px-3 py-2 text-sm font-medium text-blue-700 transition-colors rounded-md bg-blue-50 hover:bg-blue-100">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V4zM8 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1H9a1 1 0 01-1-1V4zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                            Open Board
                        </button>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="inline-flex items-center justify-center p-2 text-gray-400 transition-colors rounded-md hover:text-gray-600 hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                    </path>
                                </svg>
                            </button>

                            <div x-show="open" @click.outside="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-10 w-48 mt-2 bg-white border border-gray-200 rounded-md shadow-lg">
                                <div class="py-1">
                                    <button wire:click="viewProject({{ $project->id }})"
                                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                            <path fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        View Details
                                    </button>
                                    <button wire:click="editProject({{ $project->id }})"
                                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z">
                                            </path>
                                        </svg>
                                        Edit Project
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
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
            <p class="mb-6 text-gray-500">Create your first project to get started.</p>
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
    @else
    {{-- Table View (Default Filament) --}}
    <div>
        {{ $this->table }}
    </div>
    @endif
</x-filament::page>