<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\CampusEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_paginator_uses_bootstrap_five_markup(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(1, 10),
            total: 20,
            perPage: 10,
            currentPage: 1,
            options: ['path' => '/records'],
        );

        $markup = $paginator->links()->render();

        $this->assertStringContainsString('class="pagination"', $markup);
        $this->assertStringContainsString('class="page-item', $markup);
        $this->assertStringContainsString('class="page-link"', $markup);
        $this->assertStringNotContainsString('sm:flex', $markup);
    }

    public function test_admin_table_pagination_is_compact_and_keeps_search_in_page_links(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        foreach (range(1, 150) as $index) {
            Building::create([
                'name' => sprintf('Pagination Building %03d', $index),
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [],
                ],
                'color' => '#18375d',
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.buildings', [
                'search' => 'Pagination Building',
                'page' => 8,
            ]));

        $response
            ->assertOk()
            ->assertSee('data-pagination-from="71"', false)
            ->assertSee('data-pagination-to="80"', false)
            ->assertSee('data-pagination-total="150"', false)
            ->assertSee('data-pagination-label="buildings"', false)
            ->assertSee('class="pagination flex-wrap justify-content-center mb-0"', false)
            ->assertSee('search=Pagination%20Building', false);

        preg_match_all(
            '/class="page-link"[^>]*>(?:\\s*)?([0-9]+)(?:\\s*)?<\\/a>|class="page-link">([0-9]+)<\\/span>/',
            $response->getContent(),
            $matches,
            PREG_SET_ORDER,
        );

        $visiblePages = array_map(
            static fn (array $match): int => (int) ($match[1] !== '' ? $match[1] : $match[2]),
            $matches,
        );

        $this->assertSame([1, 2, 7, 8, 9, 14, 15], $visiblePages);
    }

    public function test_campus_events_show_the_total_and_paginate_after_ten_records(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        foreach (range(1, 12) as $index) {
            CampusEvent::create([
                'event_target_type' => 'building',
                'created_by' => $admin->id,
                'title' => sprintf('Campus Event %02d', $index),
                'starts_at' => now()->addDays($index),
                'is_active' => true,
                'priority' => $index,
            ]);
        }

        $this
            ->actingAs($admin)
            ->get(route('admin.campus-event', ['page' => 2]))
            ->assertOk()
            ->assertSee('data-pagination-from="11"', false)
            ->assertSee('data-pagination-to="12"', false)
            ->assertSee('data-pagination-total="12"', false)
            ->assertSee('data-pagination-label="campus events"', false)
            ->assertSeeText('Total Events: 12')
            ->assertSee('aria-current="page"', false);
    }

    public function test_authorized_accounts_use_the_same_pagination_summary(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => '1',
        ]);

        User::factory()
            ->count(12)
            ->create([
                'role' => 'authorized_user',
                'position' => 'Campus Officer',
                'authorized_permissions' => ['campus_events'],
                'status' => '1',
            ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.authorized.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('data-pagination-from="11"', false)
            ->assertSee('data-pagination-to="12"', false)
            ->assertSee('data-pagination-total="12"', false)
            ->assertSee('data-pagination-label="authorized accounts"', false);
    }

    public function test_authorized_users_receive_shared_pagination_on_assigned_tables(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'authorized_user',
            'position' => 'Campus Officer',
            'authorized_permissions' => ['buildings'],
            'status' => '1',
        ]);

        foreach (range(1, 11) as $index) {
            Building::create([
                'name' => sprintf('Authorized Building %02d', $index),
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [],
                ],
                'color' => '#68a7ee',
            ]);
        }

        $this
            ->actingAs($authorizedUser)
            ->get(route('admin.buildings', ['page' => 2]))
            ->assertOk()
            ->assertSee('data-pagination-from="11"', false)
            ->assertSee('data-pagination-to="11"', false)
            ->assertSee('data-pagination-total="11"', false)
            ->assertSee('class="pagination flex-wrap justify-content-center mb-0"', false);
    }

    public function test_every_admin_table_uses_the_shared_pagination_partial(): void
    {
        $tableViews = [
            'admin/buildings/building.blade.php',
            'admin/building_entrances/building_entrances.blade.php',
            'admin/building_entrance_link/building_entrance_link.blade.php',
            'admin/campus_event/campus_event.blade.php',
            'admin/Destination/Destination_keyword.blade.php',
            'admin/destination_links/index.blade.php',
            'admin/Entry_point/Entry_point.blade.php',
            'admin/hazard_point/hazard_point.blade.php',
            'admin/indoor_entrances/indoor_entrance.blade.php',
            'admin/indoor_map/indoor_map.blade.php',
            'admin/indoor_path/indoor_path.blade.php',
            'admin/indoor_room/indoor_room.blade.php',
            'admin/indoor_stairs_link/indoor_stairs_link.blade.php',
            'admin/landuse/landuse.blade.php',
            'admin/path/path.blade.php',
        ];

        foreach ($tableViews as $tableView) {
            $source = file_get_contents(resource_path("views/{$tableView}"));

            $this->assertStringContainsString(
                "@include('admin.partials.pagination'",
                $source,
                "{$tableView} should use the shared pagination partial.",
            );
            $this->assertStringNotContainsString(
                'getUrlRange(',
                $source,
                "{$tableView} should not render every page number manually.",
            );
        }

        $authorizedAccess = file_get_contents(
            resource_path('views/admin/authorized_access/index.blade.php')
        );

        $this->assertStringContainsString(
            "@include('admin.partials.pagination'",
            $authorizedAccess,
        );
    }
}
