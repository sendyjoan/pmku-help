<?php

namespace App\Providers;

use App\Settings\GeneralSettings;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Di AppServiceProvider.php dalam method boot()
        Filament::registerRenderHook(
            'head.end',
            fn (): string => '<style>
                html.dark { background-color: #111827 !important; }
                html.dark body { background-color: #111827 !important; }
                html.dark .filament-main { background-color: #111827 !important; }
                html.dark .filament-main-content { background-color: #1f2937 !important; }
                html.dark .bg-gray-100 { background-color: #111827 !important; }
            </style>'
        );
        // Configure application
        $this->configureApp();

        // Register custom Filament theme
        Filament::serving(function () {
            Filament::registerTheme(
                app(Vite::class)('resources/css/filament.scss'),
            );

            $appName = config('app.name');
            $appLogo = config('app.logo');
            $appLogoDark = config('app.logo_dark');

            if ($appLogo || $appLogoDark) {
                // Enhanced styles for dark mode logo support
                Filament::registerRenderHook(
                    'body.start',
                    fn (): string => '<style>
                        .filament-main-sidebar-brand {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        }
                        .filament-main-sidebar-brand img {
                            height: 2rem;
                            width: auto;
                            transition: opacity 0.3s ease;
                        }

                        /* Dark mode specific styles */
                        @media (prefers-color-scheme: dark) {
                            .dark .filament-main-sidebar-brand img.light-logo {
                                display: none;
                            }
                            .dark .filament-main-sidebar-brand img.dark-logo {
                                display: block;
                            }
                        }

                        /* Light mode specific styles */
                        @media (prefers-color-scheme: light) {
                            .filament-main-sidebar-brand img.light-logo {
                                display: block;
                            }
                            .filament-main-sidebar-brand img.dark-logo {
                                display: none;
                            }
                        }

                        /* Manual dark mode toggle support */
                        .dark .filament-main-sidebar-brand img:not(.dark-logo) {
                            display: none;
                        }

                        .dark .filament-main-sidebar-brand img.dark-logo {
                            display: block !important;
                        }

                        /* Fallback filter for logos without dark variant */
                        .filament-main-sidebar-brand img.auto-invert {
                            filter: brightness(0) invert(1);
                        }

                        /* Logo loading states */
                        .filament-main-sidebar-brand img[src=""] {
                            display: none;
                        }

                        /* Smooth theme transition */
                        .filament-main-sidebar-brand * {
                            transition: all 0.2s ease-in-out;
                        }
                    </style>'
                );
            }
        });

        // Register tippy styles
        Filament::registerStyles([
            'https://unpkg.com/tippy.js@6/dist/tippy.css',
        ]);

        // Register scripts
        try {
            Filament::registerScripts([
                app(Vite::class)('resources/js/filament.js'),
            ]);
        } catch (\Exception $e) {
            // Manifest not built yet!
        }

        // Add custom meta (favicon) - support for dark mode favicon too
        $favicon = config('app.logo_dark') ?: config('app.logo');
        Filament::pushMeta([
            new HtmlString('<link rel="icon" type="image/x-icon" href="' . $favicon . '">'),
            new HtmlString('<link rel="icon" type="image/x-icon" href="' . $favicon . '" media="(prefers-color-scheme: dark)">'),
        ]);

        // Register navigation groups
        Filament::registerNavigationGroups([
            __('Management'),
            __('Referential'),
            __('Security'),
            __('Settings'),
        ]);

        // Force HTTPS over HTTP
        if (env('APP_FORCE_HTTPS') ?? false) {
            URL::forceScheme('https');
        }

        Blade::component('user-avatar', \App\View\Components\UserAvatar::class);

        // Override Filament config for user avatar (for Filament v2)
        config(['filament.user.avatar' => function ($user) {
            return $user->avatar_url ?: null;
        }]);
    }

    private function configureApp(): void
    {
        try {
            $settings = app(GeneralSettings::class);
            Config::set('app.locale', $settings->site_language ?? config('app.fallback_locale'));
            Config::set('app.name', $settings->site_name ?? env('APP_NAME'));
            Config::set('filament.brand', $settings->site_name ?? env('APP_NAME'));

            // Configure light mode logo
            Config::set(
                'app.logo',
                $settings->site_logo ? asset('storage/' . $settings->site_logo) : (env('APP_LOGO') ?: asset('favicon.ico'))
            );

            // Configure dark mode logo
            $darkLogo = null;
            if (isset($settings->site_logo_dark) && $settings->site_logo_dark) {
                $darkLogo = asset('storage/' . $settings->site_logo_dark);
            } elseif (env('APP_LOGO_DARK')) {
                $darkLogo = env('APP_LOGO_DARK');
            }
            Config::set('app.logo_dark', $darkLogo);

            Config::set('filament-breezy.enable_registration', $settings->enable_registration ?? false);
            Config::set('filament-socialite.registration', $settings->enable_registration ?? false);
            Config::set('filament-socialite.enabled', $settings->enable_social_login ?? false);
            Config::set('system.login_form.is_enabled', $settings->enable_login_form ?? false);
            Config::set('services.oidc.is_enabled', $settings->enable_oidc_login ?? false);
        } catch (QueryException $e) {
            // Error: No database configured yet
        }
    }
}