<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WayfindingAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_dashboard_loads_optimized_wayfinding_entries(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $sourceAssets = [
            'css/wayfinding/01-foundation-map.css',
            'css/wayfinding/02-route-controls.css',
            'css/wayfinding/03-ai-search-voice.css',
            'css/wayfinding/04-indoor-navigation.css',
            'css/wayfinding/05-route-popup-effects.css',
            'css/wayfinding/06-path-picker-events-profile.css',
            'css/wayfinding/07-campus-theme.css',
            'css/wayfinding/08-panel-positioning.css',
            'css/wayfinding/09-map-performance.css',
            'css/wayfinding/10-campus-brand-route.css',
            'css/wayfinding/11-gps-rotation.css',
            'css/wayfinding/12-futuristic-theme.css',
            'css/wayfinding/13-navigation-accessibility.css',
            'css/wayfinding/14-gps-diagnostics.css',
            'css/wayfinding/15-pwa-offline.css',
            'css/wayfinding/16-user-friendly.css',
            'js/wayfinding-routing.js',
            'js/wayfinding/01-map-core.js',
            'js/wayfinding/02-map-data-ui.js',
            'js/wayfinding/03-outdoor-routing.js',
            'js/wayfinding/04-indoor-routing.js',
            'js/wayfinding/05-map-rendering.js',
            'js/wayfinding/06-search-voice.js',
            'js/wayfinding/07-campus-events-data.js',
            'js/wayfinding/08-navigation-accessibility.js',
            'js/wayfinding/08-assistant-ui.js',
            'js/wayfinding/09-building-indoor-ui.js',
            'js/wayfinding/10-responsive-performance.js',
            'js/wayfinding/11-map-performance.js',
            'js/wayfinding/12-gps-tracking.js',
            'js/wayfinding/13-gps-diagnostics.js',
            'js/wayfinding/14-pwa-offline.js',
        ];

        foreach ($sourceAssets as $asset) {
            $this->assertFileExists(public_path($asset));
        }

        $dashboard = file_get_contents(
            resource_path('views/user/dashboard.blade.php')
        );
        $this->assertStringContainsString(
            "@vite('resources/css/wayfinding.css')",
            $dashboard,
        );
        $this->assertStringContainsString(
            "@vite('resources/js/wayfinding-entry.js')",
            $dashboard,
        );
        $this->assertStringNotContainsString(
            "@include('user.style.style')",
            $dashboard,
        );
        $this->assertStringNotContainsString(
            "@include('user.script.script')",
            $dashboard,
        );

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard'));

        $response
            ->assertOk()
            ->assertSee('id="wfDialogBackdrop"', escape: false)
            ->assertSee('window.FuturisticDialog', escape: false)
            ->assertDontSee(asset('js/wayfinding/01-map-core.js'), escape: false)
            ->assertDontSee(asset('css/wayfinding/01-foundation-map.css'), escape: false)
            ->assertDontSee('FINAL FAKE 3D LAG REDUCER PATCH');
    }

    public function test_user_dashboard_exposes_accessible_navigation_and_recovery_regions(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard'));

        $response
            ->assertOk()
            ->assertSee('id="navigation-sheet"', escape: false)
            ->assertSee('id="navigation-details-toggle"', escape: false)
            ->assertSee('id="navigation-details-toggle-label">Route', escape: false)
            ->assertSee('id="cr-navigation-toggle"', escape: false)
            ->assertSee('id="cr-navigation-modal"', escape: false)
            ->assertSee('Find the Nearest CR', escape: false)
            ->assertSee('data-cr-mode="gps"', escape: false)
            ->assertSee('data-cr-mode="path"', escape: false)
            ->assertSee('data-cr-mode="default"', escape: false)
            ->assertSee('15-cr-navigation.js', escape: false)
            ->assertSee('id="route-result-label"', escape: false)
            ->assertSee('aria-live="polite"', escape: false)
            ->assertSee('id="wayfinding-connection-banner"', escape: false)
            ->assertSee('id="wayfinding-retry-btn"', escape: false)
            ->assertSee('id="navigation-gps-quality"', escape: false)
            ->assertSee('Select Current Location when ready', escape: false)
            ->assertDontSee('id="gps-diagnostics-toggle"', escape: false)
            ->assertDontSee('id="gps-diagnostics-panel"', escape: false)
            ->assertDontSee('Real-device field test', escape: false)
            ->assertSee('rel="manifest"', escape: false)
            ->assertSee('id="pwa-profile-status"', escape: false)
            ->assertSee('id="pwa-install-button"', escape: false)
            ->assertSee('id="pwa-update-banner"', escape: false)
            ->assertSee('role="dialog"', escape: false)
            ->assertSee('aria-modal="true"', escape: false)
            ->assertSee('aria-label="Choose a destination"', escape: false);
    }

    public function test_gps_simulator_is_hidden_outside_local_debug_mode(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard', ['gps_simulator' => 1]));

        $response
            ->assertOk()
            ->assertDontSee('css/wayfinding/13-gps-simulator.css', escape: false)
            ->assertDontSee('js/wayfinding/13-gps-simulator.js', escape: false)
            ->assertDontSee('WAYFINDING_GPS_SIMULATOR_ENABLED', escape: false);
    }

    public function test_gps_simulator_loads_for_an_explicit_local_debug_request(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        config(['app.debug' => true]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard', ['gps_simulator' => 1]));

        $dashboard = file_get_contents(
            resource_path('views/user/dashboard.blade.php')
        );
        $flagPosition = strpos(
            $dashboard,
            'window.WAYFINDING_GPS_SIMULATOR_ENABLED = true;',
        );
        $entryPosition = strpos(
            $dashboard,
            "@vite('resources/js/wayfinding-entry.js')",
        );
        $simulatorPosition = strpos(
            $dashboard,
            "asset('js/wayfinding/13-gps-simulator.js')",
        );

        $this->assertIsInt($flagPosition);
        $this->assertIsInt($entryPosition);
        $this->assertIsInt($simulatorPosition);
        $this->assertLessThan($entryPosition, $flagPosition);
        $this->assertLessThan($simulatorPosition, $entryPosition);

        $response
            ->assertOk()
            ->assertSee('css/wayfinding/13-gps-simulator.css', escape: false)
            ->assertSee('js/wayfinding/13-gps-simulator.js', escape: false)
            ->assertSee('window.WAYFINDING_GPS_SIMULATOR_ENABLED = true;', escape: false)
            ->assertSee('id="gps-diagnostics-toggle"', escape: false)
            ->assertSee('id="gps-diagnostics-panel"', escape: false)
            ->assertSee('id="gps-session-export"', escape: false)
            ->assertSee('Coordinates stay on this device', escape: false)
            ->assertSeeInOrder([
                'window.WAYFINDING_GPS_SIMULATOR_ENABLED = true;',
                'js/wayfinding/13-gps-simulator.js',
            ], escape: false);
    }

    public function test_gps_diagnostics_can_be_explicitly_enabled_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard', ['gps_diagnostics' => 1]));

        $response
            ->assertOk()
            ->assertSee('id="gps-diagnostics-toggle"', escape: false)
            ->assertSee('id="gps-diagnostics-panel"', escape: false)
            ->assertSee('Real-device field test', escape: false);
    }

    public function test_pwa_files_define_safe_offline_caching_boundaries(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $serviceWorkerPath = public_path('sw.js');
        $offlinePath = public_path('offline.html');

        $this->assertFileExists($manifestPath);
        $this->assertFileExists($serviceWorkerPath);
        $this->assertFileExists($offlinePath);
        $this->assertFileExists(public_path('icons/pwa-icon-192.png'));
        $this->assertFileExists(public_path('icons/pwa-icon-512.png'));
        $this->assertFileExists(public_path('js/wayfinding/15-cr-navigation.js'));
        $this->assertFileExists(public_path('css/wayfinding/17-cr-navigation.css'));

        $manifest = json_decode(
            file_get_contents($manifestPath),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $serviceWorker = file_get_contents($serviceWorkerPath);

        $this->assertSame('/user/dashboard?source=pwa', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#18375d', $manifest['theme_color']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertStringContainsString("const OFFLINE_URL = '/offline.html';", $serviceWorker);
        $this->assertStringContainsString("fetch('/build/manifest.json'", $serviceWorker);
        $this->assertStringContainsString('collectViteAssetUrls(manifest)', $serviceWorker);
        $this->assertStringContainsString('entry?.isEntry === true', $serviceWorker);
        $this->assertStringContainsString('(entry.imports || []).forEach(collectEntry)', $serviceWorker);
        $this->assertStringNotContainsString('entry.dynamicImports', $serviceWorker);
        $this->assertStringContainsString('/js/wayfinding/15-cr-navigation.js', $serviceWorker);
        $this->assertStringContainsString('/css/wayfinding/17-cr-navigation.css', $serviceWorker);
        $this->assertStringContainsString("'/api/buildings'", $serviceWorker);
        $this->assertStringContainsString("'/api/indoor-paths'", $serviceWorker);
        $this->assertStringContainsString('if (url.origin !== self.location.origin) return;', $serviceWorker);
        $this->assertStringNotContainsString("'/user/dashboard'", $serviceWorker);
        $this->assertStringNotContainsString('tile.openstreetmap.org', $serviceWorker);
    }
}
