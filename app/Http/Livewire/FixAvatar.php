<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAvatar extends Component
{
    public function mount()
    {
        // Cek apakah user login
        if (auth()->check()) {
            $userId = auth()->id();

            // Debug log
            Log::info("FixAvatar: Checking avatar for user ID: {$userId}");

            // Cek avatar_url langsung dari database
            $avatarUrlFromDB = DB::table('users')
                ->where('id', $userId)
                ->value('avatar_url');

            Log::info("FixAvatar: avatar_url from DB: " . ($avatarUrlFromDB ?: 'NULL'));

            // Cek avatar_url dari model
            $avatarUrlFromModel = auth()->user()->avatar_url;
            Log::info("FixAvatar: avatar_url from Model: " . ($avatarUrlFromModel ?: 'NULL'));

            // Jika avatar_url ada di database tapi tidak di model
            if ($avatarUrlFromDB && !$avatarUrlFromModel) {
                Log::warning("FixAvatar: Inconsistency detected! avatar_url exists in DB but not in model");

                // Coba update user model dengan benar
                auth()->user()->forceFill(['avatar_url' => $avatarUrlFromDB])->save();
                Log::info("FixAvatar: Forced avatar_url update in model");
            }

            // Cek jika provider yang benar digunakan
            $providerClass = config('filament.default_avatar_provider');
            Log::info("FixAvatar: Current avatar provider: {$providerClass}");

            // Force override method untuk mendapatkan avatar di session
            session(['force_avatar_url' => $avatarUrlFromDB]);
            Log::info("FixAvatar: Set forced avatar URL in session");
        }
    }

    public function render()
    {
        return <<<'BLADE'
        <div style="display: none;">Avatar Fixer Running</div>

        <script>
            // JS Fix untuk avatar
            document.addEventListener('DOMContentLoaded', function() {
                // Force avatar image di seluruh halaman
                let avatarUrl = "{{ auth()->check() ? (auth()->user()->avatar_url ?: '') : '' }}";

                if (avatarUrl) {
                    console.log("Fixing avatar images with:", avatarUrl);

                    // Tunggu 1 detik untuk memastikan DOM sudah ready
                    setTimeout(function() {
                        // Cari semua avatar image dan ganti src
                        document.querySelectorAll('.fi-avatar img, .fi-user-avatar, .filament-user-avatar').forEach(function(img) {
                            img.src = avatarUrl;
                            img.style.objectFit = 'cover';
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.borderRadius = '50%';

                            // Pastikan parent element juga memiliki overflow hidden
                            if (img.parentElement) {
                                img.parentElement.style.overflow = 'hidden';
                                img.parentElement.style.borderRadius = '50%';
                            }

                            console.log("Avatar fixed:", img);
                        });
                    }, 1000);
                }
            });
        </script>
        BLADE;
    }
}
