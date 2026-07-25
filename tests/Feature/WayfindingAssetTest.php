<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WayfindingAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dashboard_loads_modular_styles_and_scripts_in_order(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $styleAssets = [
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
        ];

        $scriptAssets = [
            'js/wayfinding-routing.js',
            'js/wayfinding/01-map-core.js',
            'js/wayfinding/02-map-data-ui.js',
            'js/wayfinding/03-outdoor-routing.js',
            'js/wayfinding/04-indoor-routing.js',
            'js/wayfinding/05-map-rendering.js',
            'js/wayfinding/06-search-voice.js',
            'js/wayfinding/07-campus-events-data.js',
            'js/wayfinding/08-assistant-ui.js',
            'js/wayfinding/09-building-indoor-ui.js',
            'js/wayfinding/10-responsive-performance.js',
            'js/wayfinding/11-map-performance.js',
            'js/wayfinding/12-gps-tracking.js',
        ];

        foreach ([...$styleAssets, ...$scriptAssets] as $asset) {
            $this->assertFileExists(public_path($asset));
        }

        $response = $this
            ->actingAs($user)
            ->get(route('user.dashboard'));

        $response
            ->assertOk()
            ->assertSeeInOrder(
                array_map(fn (string $asset): string => asset($asset), $styleAssets),
                escape: false,
            )
            ->assertSeeInOrder(
                array_map(fn (string $asset): string => asset($asset), $scriptAssets),
                escape: false,
            )
            ->assertDontSee('FINAL FAKE 3D LAG REDUCER PATCH');
    }
}
