<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_database_metrics_and_recent_sensitive_activity(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $admin = $this->createSuperAdmin();
        $storeRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $activeWarehouse = $this->createWarehouse('WH-ACTIVE', true);
        $this->createWarehouse('WH-INACTIVE', false);

        $inactiveUser = $this->createUser('Inactive User', 'inactive-user', false);
        $latestUser = $this->createUser('Latest User', 'latest-user', true);
        $latestUser->assignRole($storeRole);
        $latestUser->gudang()->attach($activeWarehouse);
        $latestUser->forceFill([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ])->saveQuietly();
        $inactiveUser->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();
        $admin->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $this->createAuditLog($admin, User::class, 'updated', 'Perubahan akun pengguna');
        $this->createAuditLog($admin, Barang::class, 'created', 'Pencatatan barang');
        $this->createAuditLog($admin, Gudang::class, 'updated', 'Perubahan gudang', now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewIs('super-admin.dashboard');
        $response->assertViewHas('stats', [
            'total_users' => 3,
            'active_users' => 2,
            'total_roles' => 2,
            'default_roles' => 2,
            'custom_roles' => 0,
            'active_gudangs' => 1,
            'active_store_gudangs' => 1,
            'active_business_gudangs' => 0,
            'audit_logs_today' => 2,
        ]);
        $response->assertViewHas('recentUsers', fn ($users): bool => $users->getCollection()->first()->is($latestUser));
        $response->assertViewHas('recentSystemLogs', function ($logs): bool {
            return $logs->count() === 2
                && $logs->first()['action'] === 'UPDATE USER'
                && $logs->first()['description'] === 'Perubahan akun pengguna';
        });
        $response->assertSee('Latest User');
        $response->assertSee('Aktivitas Sensitif Terakhir');
        $response->assertSee('TOTAL PENGGUNA AKTIF');
    }

    public function test_user_table_filters_by_search_role_and_status(): void
    {
        $admin = $this->createSuperAdmin();
        $storeRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $matchingUser = $this->createUser('Store Operator', 'store-operator', true);
        $matchingUser->assignRole($storeRole);
        $this->createUser('Inactive Operator', 'inactive-operator', false);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'search' => 'store',
            'role' => 'Admin Gudang Store',
            'status' => 'active',
        ]));

        $response->assertViewHas('recentUsers', function ($users) use ($matchingUser): bool {
            return $users->total() === 1
                && $users->getCollection()->first()->is($matchingUser);
        });
        $response->assertSee('Store Operator');
        $response->assertDontSee('Inactive Operator');
    }

    public function test_central_dashboard_route_is_removed_in_favor_of_role_specific_dashboards(): void
    {
        $this->assertFalse(Route::has('dashboard'));
        $this->assertFalse(Route::has('unit-bisnis.dashboard'));
        $this->assertFalse(Route::has('admin-gu.dashboard'));
        $this->assertFalse(Route::has('recording-gu.dashboard'));
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertTrue(Route::has('store.dashboard'));
        $this->assertTrue(Route::has('unit-bisnis.admin-gu.dashboard'));
        $this->assertTrue(Route::has('unit-bisnis.recording-gu.dashboard'));
    }

    public function test_admin_dashboard_forbids_users_without_super_admin_role(): void
    {
        $user = $this->createUser('Regular User', 'regular-user', true);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_redirects_guests_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_dashboard_batches_warehouse_assignment_queries(): void
    {
        $admin = $this->createSuperAdmin();
        $warehouseQueries = [];

        DB::listen(function (QueryExecuted $query) use (&$warehouseQueries): void {
            if (str_contains(strtolower($query->sql), 'user_gudang')) {
                $warehouseQueries[] = $query->sql;
            }
        });

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Sistem Global');
        $this->assertCount(1, $warehouseQueries, implode("\n", $warehouseQueries));
    }

    private function createSuperAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = $this->createUser('Super Admin', 'super-admin', true);
        $admin->assignRole($role);

        return $admin;
    }

    private function createUser(string $name, string $username, bool $isActive): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'name' => $name,
            'username' => $username,
            'is_aktif' => $isActive,
        ]));
    }

    private function createWarehouse(string $code, bool $isActive): Gudang
    {
        return Gudang::withoutEvents(fn (): Gudang => Gudang::create([
            'kode' => $code,
            'nama' => $code,
            'jenis' => 'gudang_store',
            'is_aktif' => $isActive,
        ]));
    }

    private function createAuditLog(
        User $actor,
        string $subjectType,
        string $action,
        string $description,
        ?Carbon $createdAt = null,
    ): void {
        AuditLog::forceCreate([
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $actor->id,
            'description' => $description,
            'ip_address' => '192.0.2.10',
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
