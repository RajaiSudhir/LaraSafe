<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddXsrfTokenCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('get') && !$request->routeIs('sanctum.*')) {
            $response->headers->setCookie(
                cookie('XSRF-TOKEN', csrf_token(), 0, '/', null, true, false, false, 'Lax')
            );
        }

        return $response;
    }
}
