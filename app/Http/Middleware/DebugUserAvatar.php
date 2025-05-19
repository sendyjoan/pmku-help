<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugUserAvatar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Log user avatar info if authenticated
        if (auth()->check()) {
            $user = auth()->user();

            Log::info('Debug User Avatar Middleware', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'avatar_url' => $user->avatar_url ?? 'NULL',
                'has_avatar_url' => isset($user->avatar_url),
                'avatar_url_empty' => empty($user->avatar_url),
                'route' => $request->route()->getName(),
                'user_class' => get_class($user),
                'user_model_attributes' => $user->getAttributes(),
            ]);
        }

        return $next($request);
    }
}
