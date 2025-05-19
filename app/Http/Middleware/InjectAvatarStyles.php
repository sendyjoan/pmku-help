<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectAvatarStyles
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
        $response = $next($request);

        // Check if the user is authenticated and has an avatar
        if (auth()->check() && auth()->user()->avatar_url && method_exists($response, 'getContent')) {
            $content = $response->getContent();

            // Only modify HTML responses
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false || !$response->headers->has('Content-Type')) {
                // Add the avatar URL as a CSS variable to the <body> tag
                $avatarUrl = auth()->user()->avatar_url;
                $style = "<style>body { --avatar-url: url('{$avatarUrl}'); }</style>";

                // Add class to body for CSS targeting
                $content = str_replace('<body ', '<body class="has-avatar" ', $content);

                // Insert the style tag right after the <head> tag
                $content = str_replace('</head>', $style . '</head>', $content);

                $response->setContent($content);
            }
        }

        return $response;
    }
}
