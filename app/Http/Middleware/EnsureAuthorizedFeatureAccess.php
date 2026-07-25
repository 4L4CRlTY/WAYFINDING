<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthorizedFeatureAccess
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessFeature($feature)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this feature.');
        }

        return $next($request);
    }
}
