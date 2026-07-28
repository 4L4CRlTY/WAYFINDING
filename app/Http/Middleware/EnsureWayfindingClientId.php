<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureWayfindingClientId
{
    public const COOKIE_NAME = 'wayfinding_client_id';

    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->cookie(self::COOKIE_NAME);
        $shouldSetCookie = ! is_string($clientId) || ! Str::isUuid($clientId);

        if ($shouldSetCookie) {
            $clientId = (string) Str::uuid();
        }

        // ThrottleRequests runs after this middleware and reads the normalized
        // value from the request rather than treating a shared campus IP as one user.
        $request->attributes->set(self::COOKIE_NAME, $clientId);
        $request->cookies->set(self::COOKIE_NAME, $clientId);

        $response = $next($request);

        if ($shouldSetCookie) {
            $response->headers->setCookie(Cookie::create(
                self::COOKIE_NAME,
                $clientId,
                now()->addYear(),
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                Cookie::SAMESITE_LAX,
            ));
        }

        return $response;
    }
}
