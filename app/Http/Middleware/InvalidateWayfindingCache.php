<?php

namespace App\Http\Middleware;

use App\Support\WayfindingCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvalidateWayfindingCache
{
    public function __construct(private readonly WayfindingCache $wayfindingCache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $request->isMethodSafe()
            && $response->getStatusCode() < Response::HTTP_BAD_REQUEST
        ) {
            $this->wayfindingCache->invalidate();
        }

        return $response;
    }
}
