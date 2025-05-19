<?php

namespace App\Providers;

use Devaslanphp\FilamentAvatar\Core\FilamentUserAvatarProvider;
use Illuminate\Database\Eloquent\Model;

class CustomFilamentUserAvatarProvider extends FilamentUserAvatarProvider
{
    public function get(Model $user): string
    {
        // Prioritaskan avatar_url jika ada
        if (isset($user->avatar_url) && !empty($user->avatar_url)) {
            return $user->avatar_url;
        }

        // Jika tidak ada avatar_url, gunakan logika default dari package
        return parent::get($user);
    }
}