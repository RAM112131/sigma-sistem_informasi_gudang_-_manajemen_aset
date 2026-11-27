<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_recorded_in_activity_log(): void
    {
        $user = User::create([
            'name' => 'Alice Tester',
            'username' => 'alice',
            'password' => Hash::make('password'),
            'is_aktif' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'alice',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'causer_id' => $user->id,
            'causer_type' => User::class,
            'action' => 'login',
        ]);
    }

    #[DataProvider('roleDashboardRoutes')]
    public function test_login_redirects_to_the_dashboard_for_the_users_role(?string $roleName, string $dashboardRoute): void
    {
        $user = User::factory()->create();

        if ($roleName !== null) {
            $user->assignRole(Role::create(['name' => $roleName, 'guard_name' => 'web']));
        }

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route($dashboardRoute));
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function roleDashboardRoutes(): array
    {
        return [
            'super admin' => ['Super Admin', 'admin.dashboard'],
            'store admin' => ['Admin Gudang Store', 'store.dashboard'],
            'unit admin' => ['Admin Gudang Umum', 'unit-bisnis.admin-gu.dashboard'],
            'unit recorder' => ['Recording Gudang Umum', 'unit-bisnis.recording-gu.dashboard'],
            'unassigned role' => [null, 'pending-access'],
        ];
    }

    public function test_authenticated_page_visit_is_recorded_as_read_activity(): void
    {
        $user = User::create([
            'name' => 'Bob Tester',
            'username' => 'bob',
            'password' => Hash::make('password'),
            'is_aktif' => true,
        ]);

        $role = Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'causer_id' => $user->id,
            'causer_type' => User::class,
            'action' => 'read',
        ]);
    }

    public function test_loggable_stores_null_causer_when_no_authenticated_user(): void
    {
        // Simulasi konteks CLI/queue — tidak ada user yang login
        Auth::logout();

        $user = User::withoutEvents(fn (): User => User::factory()->create([
            'is_aktif' => true,
        ]));

        // Update langsung tanpa auth — Loggable harus menyimpan causer_id = null
        $user->is_aktif = false;
        $user->save();

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'action' => 'updated',
            'causer_id' => null,
            'causer_type' => null,
        ]);
    }

    public function test_loggable_excludes_hidden_fields_from_change_log(): void
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create([
            'is_aktif' => true,
            'password' => Hash::make('original'),
        ]));

        // Update password dan field lain — password tidak boleh tersimpan di audit log
        $user->password = Hash::make('newpassword');
        $user->is_aktif = false;
        $user->save();

        /** @var AuditLog|null $log */
        $log = AuditLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($log, 'Audit log harus ada setelah update user');
        $changes = json_decode((string) $log->getRawOriginal('changes'), true, flags: JSON_THROW_ON_ERROR);

        if (is_string($changes)) {
            $changes = json_decode($changes, true, flags: JSON_THROW_ON_ERROR);
        }

        $this->assertIsArray($changes);
        $this->assertArrayNotHasKey('password', $changes['sebelum']);
        $this->assertArrayNotHasKey('remember_token', $changes['sebelum']);
        $this->assertArrayHasKey('is_aktif', $changes['sebelum']);
    }
}
