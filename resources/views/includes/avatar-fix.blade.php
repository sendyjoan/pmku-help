@if(auth()->check() && auth()->user()->avatar_url)
<script>
    // Fix untuk avatar di Filament
document.addEventListener('DOMContentLoaded', function() {
    const avatarUrl = "{{ auth()->user()->avatar_url }}";
    console.log("Avatar Fix from included view: Using", avatarUrl);

    function fixAvatars() {
        document.querySelectorAll('.fi-avatar img, .fi-user-avatar, .filament-user-avatar, .avatar-with-initials').forEach(function(img) {
            if (img.src && (img.src.includes('ui-avatars.com') || img.src.includes('gravatar.com'))) {
                console.log("Avatar Fix: Replacing", img.src);
                img.src = avatarUrl;
                img.style.objectFit = 'cover';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.borderRadius = '50%';

                // Pastikan parent element juga memiliki overflow hidden
                if (img.parentElement) {
                    img.parentElement.style.borderRadius = '50%';
                    img.parentElement.style.overflow = 'hidden';
                }
            }
        });
    }

    // Run immediately
    fixAvatars();

    // And also run periodically to catch dynamically loaded content
    setInterval(fixAvatars, 2000);

    // Also fix when navigation happens in SPA mode
    document.addEventListener('turbo:load', fixAvatars);
    document.addEventListener('livewire:load', fixAvatars);
});
</script>

<style>
    /* Override any default avatar styling */
    .fi-avatar img[src^="{{ auth()->user()->avatar_url }}"],
    .fi-user-avatar[src^="{{ auth()->user()->avatar_url }}"],
    .filament-user-avatar[src^="{{ auth()->user()->avatar_url }}"] {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 50% !important;
    }

    /* Ensure parent containers respect the border radius */
    .fi-avatar,
    .fi-user-menu button,
    .filament-user-menu button {
        overflow: hidden !important;
        border-radius: 50% !important;
    }
</style>
@endif
