<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddXsrfTokenCookie
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Force add XSRF-TOKEN cookie on every response
        if (!$response->headers->getCookies()) {
            $response->headers->setCookie(
                cookie(
                    'XSRF-TOKEN',
                    csrf_token(),
                    60 * config('session.lifetime'),
                    config('session.path'),
                    config('session.domain'),
                    config('session.secure'),
                    false,  // HttpOnly MUST be false for JavaScript access
                    false,
                    config('session.same_site')
                )
            );
        }

        return $response;
    }
}
