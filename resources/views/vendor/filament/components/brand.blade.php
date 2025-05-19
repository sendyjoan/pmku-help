@php
$appLogo = env('APP_LOGO');
$appName = config('app.name');
@endphp

<div class="flex items-center gap-3">
    @if($appLogo)
    <img src="{{ $appLogo }}" alt="{{ $appName }}" class="object-contain w-auto h-20"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
    <span class="hidden text-xl font-semibold">{{ $appName }}</span>
    @else
    <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ $appName }}</span>
    @endif
</div>