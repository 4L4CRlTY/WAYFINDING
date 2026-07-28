<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WayfindingCache
{
    private const VERSION_KEY = 'wayfinding.api.cache-version';

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function responseKey(Request $request): string
    {
        $version = $this->version();
        $requestKey = hash('sha256', $request->method().'|'.$request->fullUrl());

        return "wayfinding.api.v{$version}.response.{$requestKey}";
    }

    public function invalidate(): int
    {
        $version = $this->version() + 1;

        Cache::forever(self::VERSION_KEY, $version);

        return $version;
    }
}
