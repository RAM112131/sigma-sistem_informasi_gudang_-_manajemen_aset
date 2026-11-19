<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_page_shows_live_statistics_and_escapes_user_names(): void
    {
        $admin = $this->createSuperAdmin();
        $storeRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $auditorRole = Role::create(['name' => 'Auditor Internal', 'guard_name' => 'web']);
        $warehouse = $this->createWarehouse('WH-STORE', 'Gudang Store');
        $multiRoleUser = $this->createUser("<script>alert('x')</script>", 'operator.test', true);
        $multiRoleUser->assignRole($storeRole, $auditorRole);
        $multiRoleUser->gudang()->attach($warehouse);
        $this->createUser('Inactive Operator', 'inactive-operator', false);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertViewIs('super-admin.users.index');
        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 3
            && $users->perPage() === 10);
        $response->assertViewHas('stats', fn (array $stats): bool => $stats['total_users'] === 3
            && $stats['active_users'] === 2
            && $stats['inactive_users'] === 1
            && $stats['multi_role_users'] === 1
            && $stats['top_gudang']->is($warehouse)
            && $stats['top_gudang']->users_count === 1
            && $stats['active_sessions'] === null);
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee('<script>', false);
        $response->assertSee('operator.test');
        $response->assertDontSee('@operator.test');
    }

    public function test_user_page_filters_by_search_role_warehouse_and_status(): void
    {
        $admin = $this->createSuperAdmin();
        $storeRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $warehouse = $this->createWarehouse('WH-STORE', 'Gudang Store');
        $matchingUser = $this->createUser('Store Operator', 'store.operator', true);
        $matchingUser->assignRole($storeRole);
        $matchingUser->gudang()->attach($warehouse);
        $inactiveUser = $this->createUser('Store Operator Inactive', 'store.inactive', false);
        $inactiveUser->assignRole($storeRole);
        $inactiveUser->gudang()->attach($warehouse);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'store.operator',
            'role' => 'Admin Gudang Store',
            'gudang' => (string) $warehouse->id,
            'status' => 'active',
        ]));

        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 1
            && $users->getCollection()->first()->is($matchingUser));
        $response->assertSee('Store Operator');
        $response->assertDontSee('Store Operator Inactive');
    }

    public function test_user_page_paginates_and_preserves_filters(): void
    {
        $admin = $this->createSuperAdmin();

        for ($index = 1; $index <= 11; $index++) {
            $this->createUser("Operator {$index}", "operator-{$index}", true);
        }

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'status' => 'active',
            'per_page' => 10,
            'page' => 2,
        ]));

        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 12
            && $users->currentPage() === 2
            && $users->perPage() === 10);
        $response->assertSeeText('Menampilkan 11–12 dari 12 pengguna');
        $response->assertSee('name="status" value="active"', false);
        $response->assertSee('name="per_page"', false);
    }

    public function test_user_page_redirects_guests_to_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_page_forbids_users_without_super_admin_role(): void
    {
        $user = $this->createUser('Regular User', 'regular-user', true);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_create_and_edit_pages_forbid_users_without_super_admin_role(): void
    {
        $regularUser = $this->createUser('Regular User', 'regular-user', true);
        $targetUser = $this->createUser('Target User', 'target-user', true);

        $this->actingAs($regularUser)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this->actingAs($regularUser)
            ->get(route('admin.users.edit', $targetUser))
            ->assertForbidden();
    }

    public function test_create_page_uses_database_roles_and_warehouses(): void
    {
        $admin = $this->createSuperAdmin();
        $permission = Permission::create(['name' => 'warehouse.audit', 'guard_name' => 'web']);
        $role = Role::create([
            'name' => 'Auditor Internal',
            'guard_name' => 'web',
            'description' => 'Melakukan audit gudang.',
        ]);
        $role->givePermissionTo($permission);
        $warehouse = $this->createWarehouse('WH-AUDIT', 'Gudang Audit');

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertViewIs('super-admin.users.form');
        $response->assertSee('Tambah Pengguna Baru');
        $response->assertSee('name="username"', false);
        $response->assertDontSee('<span aria-hidden="true">@</span>', false);
        $response->assertSee('name="roles[]"', false);
        $response->assertSee('Melakukan audit gudang.');
        $response->assertSee('warehouse.audit');
        $response->assertSee($warehouse->kode);
        $response->assertSee('Ringkasan Akses Pengguna');
    }

    public function test_edit_page_preselects_existing_roles_and_warehouses(): void
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $warehouse = $this->createWarehouse('WH-STORE', 'Gudang Store');
        $user = $this->createUser('Store Operator', 'store.operator', true);
        $user->assignRole($role);
        $user->gudang()->attach($warehouse);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));

        $response->assertViewIs('super-admin.users.form');
        $response->assertSee('Edit Pengguna');
        $response->assertViewHas('formState', fn (array $formState): bool => $formState['roles'] === [$role->name]
            && $formState['gudangIds'] === [(string) $warehouse->id]
            && $formState['isActive'] === true);
    }

    public function test_store_creates_user_with_multiple_roles_warehouse_and_inactive_status(): void
    {
        $admin = $this->createSuperAdmin();
        $storeRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $auditorRole = Role::create(['name' => 'Auditor Internal', 'guard_name' => 'web']);
        $warehouse = $this->createWarehouse('WH-STORE', 'Gudang Store');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Operator',
            'username' => 'new.operator',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'is_aktif' => false,
            'roles' => [$storeRole->name, $auditorRole->name],
            'gudang_ids' => [$warehouse->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Operator',
            'username' => 'new.operator',
            'is_aktif' => false,
        ]);
        $user = User::query()->where('username', 'new.operator')->firstOrFail();
        $this->assertTrue(password_verify('password-123', $user->password));
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => User::class,
            'role_id' => $storeRole->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => User::class,
            'role_id' => $auditorRole->id,
        ]);
        $this->assertDatabaseHas('user_gudang', [
            'user_id' => $user->id,
            'gudang_id' => $warehouse->id,
        ]);
    }

    public function test_update_replaces_user_roles_and_warehouse_assignments(): void
    {
        $admin = $this->createSuperAdmin();
        $oldRole = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $newRole = Role::create(['name' => 'Auditor Internal', 'guard_name' => 'web']);
        $additionalRole = Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']);
        $oldWarehouse = $this->createWarehouse('WH-OLD', 'Gudang Lama');
        $newWarehouse = $this->createWarehouse('WH-NEW', 'Gudang Baru');
        $user = $this->createUser('Existing Operator', 'existing-operator', true);
        $user->assignRole($oldRole);
        $user->gudang()->attach($oldWarehouse);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'password' => '',
            'password_confirmation' => '',
            'roles' => [$newRole->name, $additionalRole->name],
            'is_aktif' => false,
            'gudang_ids' => [$newWarehouse->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => User::class,
            'role_id' => $oldRole->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => User::class,
            'role_id' => $newRole->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => User::class,
            'role_id' => $additionalRole->id,
        ]);
        $this->assertDatabaseMissing('user_gudang', [
            'user_id' => $user->id,
            'gudang_id' => $oldWarehouse->id,
        ]);
        $this->assertDatabaseHas('user_gudang', [
            'user_id' => $user->id,
            'gudang_id' => $newWarehouse->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_aktif' => false,
        ]);
    }

    public function test_store_rejects_password_when_confirmation_does_not_match(): void
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => 'new.operator',
                'password' => 'password-123',
                'password_confirmation' => 'different-password',
                'is_aktif' => true,
                'roles' => [$role->name],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['username' => 'new.operator']);
    }

    public function test_store_rejects_password_without_a_number(): void
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => 'new.operator',
                'password' => 'passwordonly',
                'password_confirmation' => 'passwordonly',
                'is_aktif' => true,
                'roles' => [$role->name],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['username' => 'new.operator']);
    }

    public function test_store_rejects_password_without_letters(): void
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => 'new.operator',
                'password' => '12345678',
                'password_confirmation' => '12345678',
                'is_aktif' => true,
                'roles' => [$role->name],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['username' => 'new.operator']);
    }

    public function test_store_rejects_users_without_a_role(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => 'new.operator',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'is_aktif' => true,
                'roles' => [],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['roles']);
        $this->assertDatabaseMissing('users', ['username' => 'new.operator']);
    }

    public function test_store_rejects_roles_from_a_non_web_guard(): void
    {
        $admin = $this->createSuperAdmin();
        $apiRole = Role::create(['name' => 'API Auditor', 'guard_name' => 'api']);

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => 'new.operator',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'is_aktif' => true,
                'roles' => [$apiRole->name],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['roles.0']);
        $this->assertDatabaseMissing('users', ['username' => 'new.operator']);
    }

    public function test_store_rejects_usernames_longer_than_the_database_column(): void
    {
        $admin = $this->createSuperAdmin();
        $longUsername = str_repeat('u', 51);
        $expectedMessage = trans('validation.max.string', [
            'attribute' => 'username',
            'max' => 50,
        ]);

        $response = $this->from(route('admin.users.create'))
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Operator',
                'username' => $longUsername,
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
                'is_aktif' => true,
                'roles' => ['Super Admin'],
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['username' => $expectedMessage]);
        $this->assertDatabaseMissing('users', ['username' => $longUsername]);
    }

    public function test_update_rejects_usernames_longer_than_the_database_column(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createUser('Existing Operator', 'existing-operator', true);
        $longUsername = str_repeat('u', 51);
        $expectedMessage = trans('validation.max.string', [
            'attribute' => 'username',
            'max' => 50,
        ]);

        $response = $this->from(route('admin.users.edit', $user))
            ->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'username' => $longUsername,
                'password' => '',
                'password_confirmation' => '',
                'roles' => ['Super Admin'],
                'is_aktif' => true,
            ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $response->assertSessionHasErrors(['username' => $expectedMessage]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'existing-operator',
        ]);
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

    private function createWarehouse(string $code, string $name): Gudang
    {
        return Gudang::withoutEvents(fn (): Gudang => Gudang::create([
            'kode' => $code,
            'nama' => $name,
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]));
    }
}
