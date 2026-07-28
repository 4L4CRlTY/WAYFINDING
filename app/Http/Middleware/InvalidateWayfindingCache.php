<?php

namespace App\Http\Middleware;

use App\Services\CampusSnapshotPublisher;
use App\Support\WayfindingCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InvalidateWayfindingCache
{
    public function __construct(
        private readonly WayfindingCache $wayfindingCache,
        private readonly CampusSnapshotPublisher $snapshotPublisher,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $request->isMethodSafe()
            && $response->getStatusCode() < Response::HTTP_BAD_REQUEST
        ) {
            $this->wayfindingCache->invalidate();

            if (app()->environment('testing')) {
                return $response;
            }

            try {
                $this->snapshotPublisher->publish();
            } catch (\Throwable $exception) {
                // The existing APIs remain the authoritative fallback. A
                // snapshot write failure must not undo a valid admin change.
                Log::warning('Campus snapshot regeneration failed.', [
                    'exception' => $exception,
                ]);
            }
        }

        return $response;
    }
}
