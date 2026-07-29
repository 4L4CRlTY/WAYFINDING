<?php

namespace Tests\Feature;

use Tests\TestCase;

class PerformanceConfigurationTest extends TestCase
{
    public function test_shared_hosting_defaults_keep_cache_off_mysql(): void
    {
        $cacheConfig = file_get_contents(config_path('cache.php'));
        $productionEnvironment = file_get_contents(base_path('.env.production.example'));

        $this->assertStringContainsString(
            "'default' => env('CACHE_STORE', 'file')",
            $cacheConfig,
        );
        $this->assertStringContainsString('CACHE_STORE=file', $productionEnvironment);
        $this->assertStringContainsString('SESSION_DRIVER=database', $productionEnvironment);
    }
}
