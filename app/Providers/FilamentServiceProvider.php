<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Filament\Resources\ProjectAuditResource;
use App\Filament\Widgets\ProjectAuditOverview;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Untuk Filament v2, kita perlu mengubah config untuk avatar
        Config::set('filament.default_avatar_provider', \App\Providers\CustomFilamentUserAvatarProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Add custom styles for avatar
        Filament::serving(function () {
            // Register CSS untuk avatar
            Filament::registerStyles([
                asset('css/filament-avatar.css'),
            ]);
            // Register ProjectAuditResource
            Filament::registerResources([
                ProjectAuditResource::class,
            ]);

            // Register Widget - Tambahkan ini
            Filament::registerWidgets([
                ProjectAuditOverview::class,
            ]);
        });
    }
}