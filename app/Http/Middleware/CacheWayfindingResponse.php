<?php

namespace App\Http\Middleware;

use App\Support\WayfindingCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheWayfindingResponse
{
    public function __construct(private readonly WayfindingCache $wayfindingCache) {}

    public function handle(Request $request, Closure $next, int $seconds = 600): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $key = $this->wayfindingCache->responseKey($request);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return response($cached['content'], $cached['status'])
                ->header('Content-Type', $cached['content_type'])
                ->header('X-Wayfinding-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type', 'application/json'),
            ], $seconds);

            $response->headers->set('X-Wayfinding-Cache', 'MISS');
        }

        return $response;
    }
}
