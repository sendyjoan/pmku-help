<?php

namespace App\Filament\Pages;

use App\Models\Role;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Actions\Action;
use Filament\Pages\SettingsPage;
use Illuminate\Contracts\Support\Htmlable;

class ManageGeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $settings = GeneralSettings::class;

    protected static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('Manage general settings');
    }

    protected function getHeading(): string|Htmlable
    {
        return __('Manage general settings');
    }

    protected static function getNavigationLabel(): string
    {
        return __('General');
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    protected function getFormSchema(): array
    {
        return [
            Card::make()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Grid::make(1)
                                ->columnSpan(1)
                                ->schema([
                                    FileUpload::make('site_logo')
                                        ->label(__('Site logo (Light Mode)'))
                                        ->helperText(__('Logo for light theme and general use'))
                                        ->image()
                                        ->maxSize(config('system.max_file_size')),

                                    FileUpload::make('site_logo_dark')
                                        ->label(__('Site logo (Dark Mode)'))
                                        ->helperText(__('Logo for dark theme (optional - will use light logo if not set)'))
                                        ->image()
                                        ->maxSize(config('system.max_file_size')),
                                ]),

                            Grid::make(1)
                                ->columnSpan(2)
                                ->schema([
                                    TextInput::make('site_name')
                                        ->label(__('Site name'))
                                        ->helperText(__('This is the platform name'))
                                        ->default(fn() => config('app.name'))
                                        ->required(),

                                    Toggle::make('enable_registration')
                                        ->label(__('Enable registration?'))
                                        ->helperText(__('If enabled, any user can create an account in this platform. But an administration need to give them permissions.')),

                                    Toggle::make('enable_social_login')
                                        ->label(__('Enable social login?'))
                                        ->helperText(__('If enabled, configured users can login via their social accounts.')),

                                    Toggle::make('enable_login_form')
                                        ->label(__('Enable form login?'))
                                        ->helperText(__('If enabled, a login form will be visible on the login page.')),

                                    Toggle::make('enable_oidc_login')
                                        ->label(__('Enable OIDC login?'))
                                        ->helperText(__('If enabled, an OIDC Connect button will be visible on the login page.')),

                                    Select::make('site_language')
                                        ->label(__('Site language'))
                                        ->helperText(__('The language used by the platform.'))
                                        ->searchable()
                                        ->options($this->getLanguages()),

                                    Select::make('default_role')
                                        ->label(__('Default role'))
                                        ->helperText(__('The platform default role (used mainly in OIDC Connect).'))
                                        ->searchable()
                                        ->options(Role::all()->pluck('name', 'id')->toArray()),
                                ]),
                        ]),
                ]),

            // Preview Card
            Card::make()
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('preview_title')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">' . __('Logo Preview') . '</h3>
                                <p class="text-sm text-gray-600">' . __('Preview how your logos will appear in light and dark modes') . '</p>
                            </div>
                        ')),

                    Grid::make(2)
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('logo_preview_light')
                                ->label(__('Light Mode Preview'))
                                ->content(function ($get) {
                                    $logo = $get('site_logo');
                                    if ($logo) {
                                        $url = is_string($logo) ? asset('storage/' . $logo) : $logo->temporaryUrl();
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="flex items-center justify-center p-4 bg-white border border-gray-200 rounded-lg dark:bg-gray-100 dark:border-gray-300">
                                                <img src="' . $url . '" alt="Light Logo Preview" class="w-auto max-h-16">
                                            </div>
                                        ');
                                    }
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="flex items-center justify-center p-4 text-gray-500 bg-white border border-gray-200 rounded-lg dark:bg-gray-100 dark:border-gray-300 dark:text-gray-600">
                                            No light logo uploaded
                                        </div>
                                    ');
                                }),

                            \Filament\Forms\Components\Placeholder::make('logo_preview_dark')
                                ->label(__('Dark Mode Preview'))
                                ->content(function ($get) {
                                    $darkLogo = $get('site_logo_dark');
                                    $lightLogo = $get('site_logo');

                                    if ($darkLogo) {
                                        $url = is_string($darkLogo) ? asset('storage/' . $darkLogo) : $darkLogo->temporaryUrl();
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="flex items-center justify-center p-4 bg-gray-800 border border-gray-600 rounded-lg dark:bg-gray-900 dark:border-gray-700">
                                                <img src="' . $url . '" alt="Dark Logo Preview" class="w-auto max-h-16">
                                            </div>
                                        ');
                                    } elseif ($lightLogo) {
                                        $url = is_string($lightLogo) ? asset('storage/' . $lightLogo) : $lightLogo->temporaryUrl();
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="relative flex items-center justify-center p-4 bg-gray-800 border border-gray-600 rounded-lg dark:bg-gray-900 dark:border-gray-700">
                                                <img src="' . $url . '" alt="Light Logo (Inverted)" class="w-auto max-h-16 filter brightness-0 invert">
                                                <div class="absolute px-2 py-1 text-xs text-yellow-900 bg-yellow-500 rounded bottom-2 right-2">
                                                    Auto-inverted
                                                </div>
                                            </div>
                                        ');
                                    }
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="flex items-center justify-center p-4 text-gray-400 bg-gray-800 border border-gray-600 rounded-lg dark:bg-gray-900 dark:border-gray-700 dark:text-gray-500">
                                            <div class="text-center">
                                                <div>No dark logo uploaded</div>
                                                <small>Will use light logo with auto-invert</small>
                                            </div>
                                        </div>
                                    ');
                                }),
                        ])
                ]),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label(__('Save'));
    }

    private function getLanguages(): array
    {
        $languages = config('system.locales.list');
        asort($languages);
        return $languages;
    }
}
