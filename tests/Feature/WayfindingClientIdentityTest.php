<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureWayfindingClientId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WayfindingClientIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_dashboard_assigns_a_persistent_client_identity(): void
    {
        $response = $this->get(route('guest.dashboard'));

        $response->assertOk()
            ->assertCookie(EnsureWayfindingClientId::COOKIE_NAME);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === EnsureWayfindingClientId::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertTrue(Str::isUuid($cookie->getValue()));
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }

    public function test_existing_valid_client_identity_is_reused_without_resetting_cookie(): void
    {
        $clientId = (string) Str::uuid();

        $this->withUnencryptedCookie(EnsureWayfindingClientId::COOKIE_NAME, $clientId)
            ->get(route('guest.dashboard'))
            ->assertOk()
            ->assertCookieMissing(EnsureWayfindingClientId::COOKIE_NAME);
    }

    public function test_authenticated_dashboard_also_assigns_client_identity(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => '1',
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertCookie(EnsureWayfindingClientId::COOKIE_NAME);
    }
}
