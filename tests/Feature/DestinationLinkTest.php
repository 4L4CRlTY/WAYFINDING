<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\DestinationLink;
use App\Models\IndoorMap;
use App\Models\IndoorRoom;
use App\Models\Landuse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_campus_event_automatically_creates_its_route_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $building = $this->building('Information Technology Building');

        $this->actingAs($admin)
            ->post(route('admin.campus-event.store'), [
                'event_target_type' => 'building',
                'building_id' => $building->id,
                'title' => 'IT Building Orientation',
                'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertRedirect();

        $link = DestinationLink::firstOrFail();
        $event = CampusEvent::firstOrFail();

        $this->assertSame(40, strlen($link->token));
        $this->assertSame($event->id, $link->campus_event_id);
        $this->assertSame('IT Building Orientation', $link->title);
        $this->assertSame('building', $link->destination_type);
        $this->assertSame($building->id, $link->destination_id);
        $this->assertTrue($link->is_active);
    }

    public function test_link_page_only_manages_automatically_generated_event_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $landuse = $this->landuse('University Open Field');

        $this->actingAs($admin)
            ->post(route('admin.campus-event.store'), [
                'event_target_type' => 'landuse',
                'landuse_id' => $landuse->id,
                'title' => 'University Open Field Program',
                'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.destination-links.index'))
            ->assertOk()
            ->assertSeeText('Campus Event Route Links')
            ->assertSeeText('University Open Field Program')
            ->assertSeeText('University Open Field')
            ->assertDontSee('id="destinationLinkForm"', false)
            ->assertDontSee('Generate Link');
    }

    public function test_non_admin_accounts_cannot_manage_destination_links(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'status' => '1',
        ]);

        $this->actingAs($authorizedUser)
            ->get(route('admin.destination-links.index'))
            ->assertForbidden();
    }

    public function test_visitors_can_enter_limited_guest_mode_without_a_shared_link(): void
    {
        $this->get(route('guest.dashboard'))
            ->assertOk()
            ->assertSeeText('Guest Mode')
            ->assertSeeText('Browse Options')
            ->assertSeeText('PICK PATH')
            ->assertSeeText('DEFAULT ROUTE')
            ->assertSeeText('CR')
            ->assertSee('id="guest-upgrade-card"', false)
            ->assertSeeText('Unlock smarter campus navigation')
            ->assertSeeText('Live GPS')
            ->assertSeeText('Text Search')
            ->assertSeeText('Voice Search')
            ->assertSee('id="guest-text-search-command-btn"', false)
            ->assertSee('id="guest-voice-command-btn"', false)
            ->assertSee('class="floating-mode-btn gps guest-feature-locked"', false)
            ->assertSee('requestGuestFeatureAccess', false)
            ->assertSee(route('login'), false)
            ->assertDontSee('id="text-search-command-btn"', false)
            ->assertDontSee('id="voice-command-btn"', false)
            ->assertSee('window.WAYFINDING_GUEST_MODE = true', false)
            ->assertSee('window.WAYFINDING_SHARED_DESTINATION = null', false);
    }

    public function test_welcome_login_and_register_pages_offer_guest_access(): void
    {
        foreach (['/', route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(route('guest.dashboard'), false)
                ->assertSeeText('Guest');
        }
    }

    public function test_guest_link_opens_limited_dashboard_and_exposes_automatic_destination(): void
    {
        $building = $this->building('Information Technology Building');
        $link = $this->destinationLink('building', $building->id);

        $this->get(route('destination-links.open', $link))
            ->assertOk()
            ->assertSeeText('Guest Mode')
            ->assertSeeText('Browse Options')
            ->assertSeeText('PICK PATH')
            ->assertSeeText('DEFAULT ROUTE')
            ->assertSeeText('CR')
            ->assertSee('id="guest-text-search-command-btn"', false)
            ->assertSee('id="guest-voice-command-btn"', false)
            ->assertSee('class="floating-mode-btn gps guest-feature-locked"', false)
            ->assertDontSee('id="text-search-command-btn"', false)
            ->assertDontSee('id="voice-command-btn"', false)
            ->assertDontSee('data-cr-mode="gps"', false)
            ->assertSee('window.WAYFINDING_GUEST_MODE = true', false)
            ->assertSee('"type":"building"', false)
            ->assertSee('"id":'.$building->id, false)
            ->assertDontSee('id="guest-upgrade-card"', false)
            ->assertSeeText('Unlock the complete navigator');
    }

    public function test_signed_in_user_is_sent_to_the_normal_dashboard_with_the_shared_room(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => '1']);
        $room = $this->room();
        $link = $this->destinationLink('room', $room->id);

        $response = $this->actingAs($user)
            ->get(route('destination-links.open', $link));

        $response->assertRedirect(route('user.dashboard', [
            'destination_link' => $link->token,
        ]));

        $this->actingAs($user)
            ->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('window.WAYFINDING_GUEST_MODE = false', false)
            ->assertSee('"type":"room"', false)
            ->assertSeeText('Search Text')
            ->assertSeeText('Voice Search')
            ->assertSeeText('USE GPS');
    }

    public function test_landuse_link_opens_guest_mode_with_an_automatic_landuse_destination(): void
    {
        $landuse = $this->landuse('Campus Activity Field');
        $link = $this->destinationLink('landuse', $landuse->id);

        $this->get(route('destination-links.open', $link))
            ->assertOk()
            ->assertSee('window.WAYFINDING_GUEST_MODE = true', false)
            ->assertSee('"type":"landuse"', false)
            ->assertSee('"id":'.$landuse->id, false)
            ->assertSeeText('Campus Activity Field');
    }

    public function test_disabled_or_missing_destination_links_are_not_opened(): void
    {
        $building = $this->building('Unavailable Building');
        $link = $this->destinationLink('building', $building->id, false);

        $this->get(route('destination-links.open', $link))->assertNotFound();

        $building->delete();
        $link->update(['is_active' => true]);

        $this->get(route('destination-links.open', $link))->assertNotFound();
    }

    public function test_room_event_creation_automatically_exposes_a_copyable_route_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $room = $this->room();

        $this->actingAs($admin)
            ->post(route('admin.campus-event.store'), [
                'event_target_type' => 'room',
                'indoor_room_id' => $room->id,
                'title' => 'IT Laboratory Orientation',
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertRedirect();

        $event = CampusEvent::firstOrFail();
        $link = DestinationLink::where('campus_event_id', $event->id)->firstOrFail();

        $this->assertSame('room', $link->destination_type);
        $this->assertSame($room->id, $link->destination_id);

        $this->actingAs($admin)
            ->get(route('admin.campus-event'))
            ->assertOk()
            ->assertSeeText('Copy Route Link')
            ->assertSee(route('destination-links.open', $link), false);
    }

    public function test_deactivating_and_reactivating_an_event_syncs_its_route_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $event = $this->eventWithDestinationLink($admin);
        $link = $event->destinationLink;

        $this->actingAs($admin)
            ->post(route('admin.campus-event.toggle-status', $event))
            ->assertRedirect();

        $this->assertFalse($event->fresh()->is_active);
        $this->assertFalse($link->fresh()->is_active);
        $this->get(route('destination-links.open', $link))->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.campus-event.toggle-status', $event))
            ->assertRedirect();

        $this->assertTrue($event->fresh()->is_active);
        $this->assertTrue($link->fresh()->is_active);
    }

    public function test_deleting_an_event_also_deletes_its_route_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $event = $this->eventWithDestinationLink($admin);
        $linkId = $event->destinationLink->id;

        $this->actingAs($admin)
            ->delete(route('admin.campus-event.destroy', $event))
            ->assertRedirect();

        $this->assertDatabaseMissing('campus_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('destination_links', ['id' => $linkId]);
    }

    public function test_an_ended_event_route_link_can_no_longer_be_opened(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => '1']);
        $event = $this->eventWithDestinationLink($admin);
        $event->update(['ends_at' => now()->subMinute()]);

        $this->get(route('destination-links.open', $event->destinationLink))
            ->assertNotFound();
    }

    private function destinationLink(string $type, int $id, bool $active = true): DestinationLink
    {
        return DestinationLink::create([
            'token' => str_repeat('a', 39).DestinationLink::count(),
            'title' => 'Shared Destination',
            'destination_type' => $type,
            'destination_id' => $id,
            'is_active' => $active,
        ]);
    }

    private function building(string $name = 'IT Building'): Building
    {
        return Building::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);
    }

    private function room(): IndoorRoom
    {
        $map = IndoorMap::create([
            'building_id' => $this->building()->id,
            'name' => 'IT First Floor',
            'floor_number' => 1,
            'floor_label' => '1F',
            'is_active' => true,
        ]);

        return IndoorRoom::create([
            'indoor_map_id' => $map->id,
            'name' => 'Laboratory 1',
            'room_code' => 'IT-LAB-1',
            'type' => 'laboratory',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);
    }

    private function eventWithDestinationLink(User $admin): CampusEvent
    {
        $building = $this->building('Event Building');
        $event = CampusEvent::create([
            'event_target_type' => 'building',
            'building_id' => $building->id,
            'created_by' => $admin->id,
            'title' => 'Linked Campus Event',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $event->destinationLink()->create([
            'token' => str_repeat('e', 39).DestinationLink::count(),
            'title' => $event->title,
            'destination_type' => 'building',
            'destination_id' => $building->id,
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        return $event->load('destinationLink');
    }

    private function landuse(string $name): Landuse
    {
        return Landuse::create([
            'name' => $name,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [],
            ],
        ]);
    }
}
