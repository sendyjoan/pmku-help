<?php

namespace App\Providers;

use Devaslanphp\FilamentAvatar\Core\FilamentUserAvatarProvider;
use Illuminate\Support\ServiceProvider;

class CustomAvatarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FilamentUserAvatarProvider::class, function ($app) {
            return new CustomFilamentUserAvatarProvider();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
