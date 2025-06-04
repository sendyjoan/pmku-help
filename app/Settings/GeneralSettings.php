<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $site_name;
    public ?string $site_logo;
    public ?string $site_logo_dark; // New field for dark mode logo
    public ?string $site_language;
    public ?int $default_role;
    public bool $enable_registration;
    public bool $enable_social_login;
    public bool $enable_login_form;
    public bool $enable_oidc_login;

    public static function group(): string
    {
        return 'general';
    }

    public static function encrypted(): array
    {
        return [];
    }

    /**
     * Get default values for settings
     */
    public static function defaults(): array
    {
        return [
            'site_name' => env('APP_NAME', 'PMHelper'),
            'site_logo' => null,
            'site_logo_dark' => null,
            'site_language' => 'en',
            'default_role' => null,
            'enable_registration' => false,
            'enable_social_login' => false,
            'enable_login_form' => true,
            'enable_oidc_login' => false,
        ];
    }
}
