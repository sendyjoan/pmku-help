@php
$appLogo = env('APP_LOGO');
$appLogoDark = env('APP_LOGO_DARK');
$appName = config('app.name');
@endphp

<div class="flex items-center gap-3">
    @if($appLogo)
    {{-- Light Mode Logo --}}
    <img src="{{ $appLogo }}" alt="{{ $appName }}" class="object-contain w-auto h-20 dark:hidden"
        onerror="this.style.display='none';">

    {{-- Dark Mode Logo --}}
    @if($appLogoDark)
    <img src="{{ $appLogoDark }}" alt="{{ $appName }}" class="hidden object-contain w-auto h-20 dark:block"
        onerror="this.style.display='none';">
    @else
    {{-- Fallback: Use light logo with filter for dark mode if no dark logo provided --}}
    <img src="{{ $appLogo }}" alt="{{ $appName }}"
        class="hidden object-contain w-auto h-20 dark:block filter brightness-0 invert"
        onerror="this.style.display='none';">
    @endif

    {{-- Fallback text when images fail to load --}}
    <span class="hidden text-xl font-semibold text-gray-900 dark:text-white" id="fallback-text">{{ $appName }}</span>
    @else
    {{-- No logo provided, show text --}}
    <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ $appName }}</span>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Handle image load errors
    const images = document.querySelectorAll('.filament-main-sidebar-brand img');
    const fallbackText = document.getElementById('fallback-text');

    let allImagesError = true;

    images.forEach(function(img) {
        img.addEventListener('load', function() {
            allImagesError = false;
        });

        img.addEventListener('error', function() {
            this.style.display = 'none';
            // Check if all images failed to load
            const visibleImages = Array.from(images).filter(img => img.style.display !== 'none');
            if (visibleImages.length === 0 && fallbackText) {
                fallbackText.classList.remove('hidden');
            }
        });
    });
});
</script>
