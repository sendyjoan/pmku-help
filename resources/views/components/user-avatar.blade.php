@if($user)
<div>
    @php($uniqid = uniqid())
    <img src="{{ $user->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=' . substr(md5($user->id), 0, 6) . '&color=ffffff' }}"
        alt="{{ $user->name }}" data-popover-target="popover-user-{{ $user->id }}-{{ $uniqid }}"
        class="object-cover w-6 h-6 bg-gray-200 bg-center bg-cover rounded-full" loading="lazy" />

    <div data-popover id="popover-user-{{ $user->id }}-{{ $uniqid }}" role="tooltip"
        class="absolute z-10 invisible inline-block w-64 text-sm font-light text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:bg-gray-800 dark:border-gray-600">
        <div class="p-3">
            <div class="flex items-center justify-between mb-2">
                <img class="object-cover w-10 h-10 rounded-full"
                    src="{{ $user->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=' . substr(md5($user->id), 0, 6) . '&color=ffffff' }}"
                    alt="{{ $user->name }}" loading="lazy">
            </div>
            <p class="text-base font-semibold leading-none text-gray-900 dark:text-white">
                <a>{{ $user->name }}</a>
            </p>
            @if($user->username)
            <p class="mb-1 text-xs text-gray-500">
                <span>{{ $user->username }}</span>
            </p>
            @endif
            <p class="mb-3 text-sm font-normal">
                <a href="mailto:{{ $user->email }}" class="hover:underline">
                    {{ $user->email }}
                </a>
            </p>
            <p class="mb-4 text-sm font-light">
                {{ __('Member since') }}
                <a class="text-blue-600 dark:text-blue-500">
                    {{ $user->created_at->format('Y-m-d') }}
                </a>
            </p>
            <ul class="flex text-sm font-light">
                <li class="mr-2">
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ collect(($user->ticketsOwned ?? collect())
                            ->merge(($user->ticketsResponsible ?? collect())))->unique('id')->count() }}
                        </span>
                        <span>{{ __('Tickets') }}</span>
                    </div>
                </li>
                <li>
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ collect(($user->projectsOwning ?? collect())
                            ->merge(($user->projectsAffected ?? collect())))->unique('id')->count() }}
                        </span>
                        <span>{{ __('Projects') }}</span>
                    </div>
                </li>
            </ul>
        </div>
        <div data-popper-arrow></div>
    </div>
</div>
@endif
