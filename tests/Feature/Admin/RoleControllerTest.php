<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_index_displays_live_web_guard_metrics(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $customRole = Role::create(['name' => 'Auditor Custom', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'roles.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'api.manage', 'guard_name' => 'api']);

        $admin = $this->createUser('super-admin', true);
        $admin->assignRole($superAdminRole);
        $this->createUser('active-auditor', true)->assignRole($customRole);
        $this->createUser('inactive-auditor', false)->assignRole($customRole);

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertViewHas('stats', fn (array $stats): bool => $stats['total_roles'] === 2
            && $stats['default_roles'] === 1
            && $stats['custom_roles'] === 1
            && $stats['total_permissions'] === 2
            && $stats['assigned_users'] === 2
            && $stats['permission_cache_store'] === config('cache.default'));
        $response->assertViewHas('roles', fn ($roles): bool => $roles->firstWhere('name', 'Auditor Custom')['active_users_count'] === 1);
        $response->assertSee('Role &amp; Permission', false);
        $response->assertSee('Auditor Custom');
        $response->assertSee('Sinkronisasi Cache Spatie');
        $response->assertSee('href="'.route('admin.roles.create').'"', false);
        $response->assertDontSee('x-show="addRoleModal"', false);
    }

    public function test_role_create_page_displays_live_permissions_and_role_templates(): void
    {
        $webPermission = Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'user.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'role.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'gudang.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'api.manage', 'guard_name' => 'api']);
        $templateRole = Role::create(['name' => 'Auditor Template', 'guard_name' => 'web']);
        $templateRole->givePermissionTo($webPermission);
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('admin.roles.create'));

        $response->assertOk()
            ->assertViewIs('super-admin.roles.create')
            ->assertViewHas('permissionGroups', function (array $permissionGroups): bool {
                $permissions = collect($permissionGroups)
                    ->flatten(1)
                    ->keyBy('key');

                return $permissions->has('users.manage')
                    && $permissions->get('users.manage')['actions'] === ['update', 'delete']
                    && $permissions->get('user.manage')['actions'] === ['create', 'update', 'delete']
                    && $permissions->get('role.manage')['actions'] === ['create', 'update', 'delete']
                    && $permissions->get('gudang.manage')['actions'] === ['create', 'update', 'delete']
                    && ! $permissions->has('api.manage');
            })
            ->assertViewHas('roleTemplates', fn ($templates): bool => $templates->contains(
                fn (array $template): bool => $template['name'] === 'Auditor Template'
                    && $template['permissions'] === ['users.manage']
            ));
        $response->assertSee('Matriks Hak Akses');
        $response->assertSee('Salin dari role yang ada');
        $response->assertSee('LIHAT');
        $response->assertSee('BUAT');
        $response->assertSee('UBAH');
        $response->assertSee('HAPUS');
        $response->assertSee('role-permission-table');
        $response->assertSee('id="role-icon-users"', false);
        $response->assertSee('id="role-icon-warehouse"', false);
        $response->assertSee('id="role-icon-opname"', false);
        $response->assertSee('<use :href="\'#role-icon-\' + groupIcon(group)"></use>', false);
    }

    public function test_role_create_page_is_forbidden_to_users_without_super_admin_role(): void
    {
        $operatorRole = Role::create(['name' => 'Operator', 'guard_name' => 'web']);
        $operator = $this->createUser('operator', true);
        $operator->assignRole($operatorRole);

        $this->actingAs($operator)
            ->get(route('admin.roles.create'))
            ->assertForbidden();
    }

    public function test_role_edit_page_displays_saved_identity_and_permissions(): void
    {
        $permission = Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        $role = Role::create([
            'name' => 'Auditor Custom',
            'guard_name' => 'web',
            'description' => 'Akses untuk audit internal',
        ]);
        $role->givePermissionTo($permission);
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('admin.roles.edit', $role));

        $response->assertViewIs('super-admin.roles.create')
            ->assertViewHas('role', fn (Role $viewRole): bool => $viewRole->is($role)
                && $viewRole->permissions->contains('name', 'users.manage'))
            ->assertViewHas('isProtectedRole', false);
        $response->assertSee('Edit Role: Auditor Custom');
        $response->assertSee('value="Auditor Custom"', false);
        $response->assertSee('Akses untuk audit internal');
        $response->assertSee('users.manage');
        $response->assertSee('name="_method" value="PUT"', false);
        $response->assertDontSee('Salin dari role yang ada');
    }

    public function test_role_update_saves_identity_and_replaces_permissions(): void
    {
        $readPermission = Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        $managePermission = Permission::create(['name' => 'user.manage', 'guard_name' => 'web']);
        $role = Role::create([
            'name' => 'Auditor Lama',
            'guard_name' => 'web',
            'description' => 'Deskripsi lama',
        ]);
        $role->givePermissionTo($readPermission, $managePermission);
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Auditor Baru',
            'description' => 'Akses audit diperbarui',
            'permissions' => ['user.manage'],
        ]);

        $response->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', "Role 'Auditor Baru' berhasil diperbarui!");
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Auditor Baru',
            'description' => 'Akses audit diperbarui',
        ]);
        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('user.manage'));
        $this->assertFalse($role->hasPermissionTo('users.manage'));
    }

    public function test_role_update_rejects_permissions_from_another_guard(): void
    {
        Permission::create(['name' => 'api.manage', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'Auditor Custom', 'guard_name' => 'web']);
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)
            ->from(route('admin.roles.edit', $role))
            ->put(route('admin.roles.update', $role), [
                'name' => 'Auditor Custom',
                'permissions' => ['api.manage'],
            ]);

        $response->assertRedirect(route('admin.roles.edit', $role))
            ->assertSessionHasErrors('permissions.0');
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Auditor Custom',
        ]);
        $this->assertFalse($role->fresh()->permissions()->exists());
    }

    public function test_super_admin_role_edit_page_is_read_only(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = $this->createUser('super-admin', true);
        $admin->assignRole($superAdminRole);

        $response = $this->actingAs($admin)->get(route('admin.roles.edit', $superAdminRole));

        $response->assertViewHas('isProtectedRole', true);
        $response->assertSee('Role Super Admin dilindungi.');
        $response->assertSee('disabled', false);
        $response->assertDontSee('Simpan Role &amp; Hak Akses', false);
    }

    public function test_role_edit_page_is_forbidden_to_users_without_super_admin_role(): void
    {
        $operatorRole = Role::create(['name' => 'Operator', 'guard_name' => 'web']);
        $operator = $this->createUser('operator', true);
        $operator->assignRole($operatorRole);
        $role = Role::create(['name' => 'Auditor Custom', 'guard_name' => 'web']);

        $this->actingAs($operator)
            ->get(route('admin.roles.edit', $role))
            ->assertForbidden();
    }

    public function test_role_store_saves_selected_web_permissions(): void
    {
        Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Auditor Baru',
                'description' => 'Akses audit operasional',
                'permissions' => ['users.manage'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', "Role 'Auditor Baru' berhasil dibuat!");

        $role = Role::query()->where('name', 'Auditor Baru')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('users.manage'));
    }

    public function test_role_store_rejects_permissions_from_another_guard(): void
    {
        Permission::create(['name' => 'api.manage', 'guard_name' => 'api']);
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->from(route('admin.roles.create'))
            ->post(route('admin.roles.store'), [
                'name' => 'Invalid Guard Role',
                'permissions' => ['api.manage'],
            ])
            ->assertRedirect(route('admin.roles.create'))
            ->assertSessionHasErrors('permissions.0');

        $this->assertDatabaseMissing('roles', ['name' => 'Invalid Guard Role']);
    }

    public function test_cache_sync_clears_spatie_permission_cache_for_super_admin(): void
    {
        $admin = $this->createSuperAdmin();
        $cacheKey = config('permission.cache.key');
        Cache::put($cacheKey, ['cached' => true]);

        $this->actingAs($admin)
            ->post(route('admin.roles.cache-sync'))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success', 'Cache permission berhasil disinkronisasi.');

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_cache_sync_is_forbidden_to_users_without_super_admin_role(): void
    {
        $userRole = Role::create(['name' => 'Operator', 'guard_name' => 'web']);
        $user = $this->createUser('operator', true);
        $user->assignRole($userRole);

        $this->actingAs($user)
            ->post(route('admin.roles.cache-sync'))
            ->assertForbidden();
    }

    private function createSuperAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = $this->createUser('super-admin', true);
        $user->assignRole($role);

        return $user;
    }

    private function createUser(string $username, bool $isActive): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'username' => $username,
            'is_aktif' => $isActive,
        ]));
    }
}
