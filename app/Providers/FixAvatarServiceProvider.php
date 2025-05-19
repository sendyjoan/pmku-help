<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class FixAvatarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register the FixAvatar component
        Livewire::component('fix-avatar', \App\Http\Livewire\FixAvatar::class);

        // Add a directive to include the avatar fix
        Blade::directive('includeAvatarFix', function () {
            return '<?php echo view("includes.avatar-fix"); ?>';
        });

        // Add a new directive for embedding avatar fix script directly
        Blade::directive('avatarFixScript', function () {
            return '<?php if(auth()->check() && auth()->user()->avatar_url): ?>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Fix avatar images
                    const avatarUrl = "<?php echo auth()->user()->avatar_url; ?>";
                    console.log("Avatar Fix: Forcing avatar URL to", avatarUrl);

                    function fixAvatars() {
                        document.querySelectorAll(".fi-avatar img, .fi-user-avatar, .filament-user-avatar").forEach(function(img) {
                            if (img.src.includes("ui-avatars.com")) {
                                console.log("Avatar Fix: Replacing", img.src, "with", avatarUrl);
                                img.src = avatarUrl;
                                img.style.objectFit = "cover";
                                img.style.width = "100%";
                                img.style.height = "100%";
                                img.style.borderRadius = "50%";
                            }
                        });
                    }

                    // Run immediately and then periodically to catch dynamic content
                    fixAvatars();
                    setInterval(fixAvatars, 2000);
                });
                </script>
            <?php endif; ?>';
        });
    }
}
