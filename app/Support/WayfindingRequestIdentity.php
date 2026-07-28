<?php

namespace App\Support;

use App\Http\Middleware\EnsureWayfindingClientId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WayfindingRequestIdentity
{
    public static function clientKey(Request $request): string
    {
        $authenticatedId = $request->user()?->getAuthIdentifier();

        if ($authenticatedId !== null) {
            return 'user:'.$authenticatedId;
        }

        $clientId = $request->attributes->get(EnsureWayfindingClientId::COOKIE_NAME)
            ?? $request->cookie(EnsureWayfindingClientId::COOKIE_NAME);

        if (is_string($clientId) && Str::isUuid($clientId)) {
            return 'device:'.$clientId;
        }

        // Defensive fallback for a route accidentally missing the identity middleware.
        return 'fallback:'.hash(
            'sha256',
            ($request->ip() ?: 'unknown').'|'.substr((string) $request->userAgent(), 0, 200)
        );
    }

    public static function networkKey(Request $request): string
    {
        return 'network:'.($request->ip() ?: 'unknown');
    }
}
