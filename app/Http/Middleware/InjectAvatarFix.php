<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InjectAvatarFix
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

        // Only process HTML responses for logged in users with avatar_url
        if (auth()->check() &&
            auth()->user()->avatar_url &&
            $response instanceof \Illuminate\Http\Response &&
            $this->isHtmlResponse($response))
        {
            $content = $response->getContent();

            if (is_string($content) && Str::contains($content, '</body>')) {
                // Create a JS script to fix avatars
                $script = '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Fix avatar images
                    const avatarUrl = "' . auth()->user()->avatar_url . '";
                    console.log("Avatar Fix [Middleware]: Forcing avatar URL to", avatarUrl);

                    function fixAvatars() {
                        document.querySelectorAll(".fi-avatar img, .fi-user-avatar, .filament-user-avatar").forEach(function(img) {
                            if (img.src.includes("ui-avatars.com") || img.src.includes("gravatar.com")) {
                                console.log("Avatar Fix [Middleware]: Replacing", img.src, "with", avatarUrl);
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
                </script>';

                // Insert the script before the end of the body tag
                $content = str_replace('</body>', $script . '</body>', $content);
                $response->setContent($content);
            }
        }

        return $response;
    }

    /**
     * Determine if the given response is an HTML response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return bool
     */
    protected function isHtmlResponse($response)
    {
        $contentType = $response->headers->get('Content-Type');

        return $contentType && (
            Str::contains($contentType, 'text/html') ||
            Str::contains($contentType, 'application/xhtml+xml')
        );
    }
}
