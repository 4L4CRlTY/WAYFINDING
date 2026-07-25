<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WayfindingCache
{
    private const VERSION_KEY = 'wayfinding.api.cache-version';

    public function responseKey(Request $request): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        $requestKey = hash('sha256', $request->method().'|'.$request->fullUrl());

        return "wayfinding.api.v{$version}.response.{$requestKey}";
    }

    public function invalidate(): void
    {
        $current = (int) Cache::get(self::VERSION_KEY, 1);

        Cache::forever(self::VERSION_KEY, $current + 1);
    }
}
