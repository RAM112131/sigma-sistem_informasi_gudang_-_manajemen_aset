<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_activity_metrics_and_paginated_logs(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $admin = $this->createSuperAdmin();
        $this->createAuditLog($admin, 'login', 'Login berhasil');
        $this->createAuditLog($admin, 'updated', 'Mengubah data pengguna');
        $this->createAuditLog($admin, 'UPDATE_ROLE_MATRIX', 'Mengubah permission role');
        $this->createAuditLog($admin, 'deleted', 'Menghapus data lama', now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.activity-log.index'));

        $response->assertOk()
            ->assertViewIs('super-admin.activity-log.index')
            ->assertViewHas('stats', [
                'total_today' => 3,
                'authentication_events' => 1,
                'data_mutations' => 2,
                'active_actors' => 1,
            ])
            ->assertViewHas('logs', fn ($logs): bool => $logs->total() === 4
                && $logs->perPage() === 10);
        $response->assertSee('Log Aktivitas Sistem')
            ->assertSee('Periode Waktu')
            ->assertSee('Pengguna / Operator')
            ->assertSee('Aksi / Event')
            ->assertSee('Subjek')
            ->assertSee('Pencarian')
            ->assertSee('Detail Log Aktivitas')
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Detail Kejadian')
            ->assertSee('Detail Aksi')
            ->assertSeeText('Isi data transaksi disembunyikan')
            ->assertSee('‹ Sebelumnya')
            ->assertSee('Berikutnya ›')
            ->assertSee('Log bersifat read-only')
            ->assertSee('Detail')
            ->assertSee('Hapus Semua Log')
            ->assertSee('class="app-sidebar"', false);
    }

    public function test_index_filters_by_date_operator_action_subject_and_search_term(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $admin = $this->createSuperAdmin();
        $operator = $this->createUser('Operator Audit', 'operator-audit');
        $matchingLog = $this->createAuditLog(
            $operator,
            'UPDATE_ROLE_MATRIX',
            'Permission gudang.store diubah',
        );
        $this->createAuditLog(
            $operator,
            'UPDATE_ROLE_MATRIX',
            'Permission gudang.store diubah di role',
            subjectType: 'Spatie\\Permission\\Models\\Role',
        );
        $this->createAuditLog($admin, 'UPDATE_ROLE_MATRIX', 'Permission gudang.store diubah', now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.activity-log.index', [
            'date_from' => '2026-10-09',
            'date_to' => '2026-10-09',
            'operator' => $operator->id,
            'action' => 'UPDATE_ROLE_MATRIX',
            'subject' => User::class,
            'search' => 'gudang.store',
        ]));

        $response->assertOk()
            ->assertViewHas('logs', fn ($logs): bool => $logs->total() === 1
                && $logs->getCollection()->first()->is($matchingLog));
        $response->assertSee('Permission gudang.store diubah')
            ->assertDontSee('Perubahan permission katalog');
    }

    public function test_detail_endpoint_returns_actor_metadata_and_change_diff(): void
    {
        $admin = $this->createSuperAdmin();
        $log = $this->createAuditLog($admin, 'updated', 'Mengubah konfigurasi gudang', changes: [
            'sebelum' => ['is_aktif' => false, 'password' => 'old-hash', 'meta' => ['session_id' => 'private-session']],
            'sesudah' => ['is_aktif' => true, 'password' => 'new-hash'],
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.activity-log.show', $log))
            ->assertOk()
            ->assertJsonPath('actor.name', 'Super Admin')
            ->assertJsonPath('actor.username', 'super-admin')
            ->assertJsonPath('actor.roles.0', 'Super Admin')
            ->assertJsonPath('log.action', 'updated')
            ->assertJsonPath('log.changes.sebelum.is_aktif', false)
            ->assertJsonPath('log.changes.sesudah.is_aktif', true)
            ->assertJsonMissingPath('log.changes.sebelum.password')
            ->assertJsonMissingPath('log.changes.sebelum.meta.session_id')
            ->assertJsonMissingPath('log.changes.sesudah.password');
    }

    public function test_transaction_details_are_hidden_from_the_detail_endpoint(): void
    {
        $admin = $this->createSuperAdmin();
        $log = $this->createAuditLog(
            $admin,
            'FINALISASI',
            'Isi transaksi gudang rahasia',
            changes: ['sebelum' => ['qty' => 4], 'sesudah' => ['qty' => 5]],
            subjectType: 'App\\Models\\StokOpname',
        );

        $this->actingAs($admin)
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertSee('Data transaksi disembunyikan')
            ->assertDontSee('Isi transaksi gudang rahasia');

        $this->actingAs($admin)
            ->getJson(route('admin.activity-log.show', $log))
            ->assertOk()
            ->assertJsonPath('log.transaction_data_hidden', true)
            ->assertJsonPath('log.changes', null)
            ->assertJsonPath('log.description', 'Data transaksi disembunyikan');

        $response = $this->actingAs($admin)->get(route('admin.activity-log.export', [
            'format' => 'json',
            'action' => 'FINALISASI',
        ]));
        $export = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertNull($export[0]['changes']);
        $this->assertSame('Data transaksi disembunyikan', $export[0]['description']);
    }

    public function test_detail_navigation_moves_to_the_adjacent_filtered_log(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $admin = $this->createSuperAdmin();
        $logs = [];

        foreach (range(1, 12) as $number) {
            $logs[] = $this->createAuditLog(
                $admin,
                'updated',
                'Log '.$number,
                now()->subMinutes($number),
            );
        }

        $olderLog = $this->actingAs($admin)
            ->getJson(route('admin.activity-log.show', [
                $logs[9],
                'direction' => 'next',
                'action' => 'updated',
            ]))
            ->assertOk()
            ->assertJsonPath('log.description', 'Log 11')
            ->json('log.id');

        $this->assertEquals($logs[10]->getKey(), $olderLog);

        $this->actingAs($admin)
            ->getJson(route('admin.activity-log.show', [
                $logs[10],
                'direction' => 'previous',
                'action' => 'updated',
            ]))
            ->assertOk()
            ->assertJsonPath('log.description', 'Log 10');
    }

    public function test_index_rejects_a_date_range_that_ends_before_it_starts(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->from(route('admin.activity-log.index'))
            ->get(route('admin.activity-log.index', [
                'date_from' => '2026-10-10',
                'date_to' => '2026-10-09',
            ]))
            ->assertRedirect(route('admin.activity-log.index'))
            ->assertSessionHasErrors('date_to');
    }

    public function test_csv_export_contains_only_matching_activity_and_neutralizes_formulas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $admin = $this->createSuperAdmin();
        $this->createAuditLog($admin, 'login', 'Login berhasil');
        $this->createAuditLog($admin, 'updated', '=1+1');

        $response = $this->actingAs($admin)->get(route('admin.activity-log.export', [
            'format' => 'csv',
            'action' => 'updated',
        ]));

        $response->assertDownload('log-aktivitas-2026-10-09.csv');
        $content = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $content);
        $this->assertStringNotContainsString('Login berhasil', $content);
    }

    public function test_json_export_returns_a_downloadable_array(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $admin = $this->createSuperAdmin();
        $this->createAuditLog($admin, 'login', 'Login berhasil');

        $response = $this->actingAs($admin)->get(route('admin.activity-log.export', [
            'format' => 'json',
        ]));

        $response->assertDownload('log-aktivitas-2026-10-09.json');
        $export = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Login berhasil', $export[0]['description']);
    }

    public function test_guests_and_non_admin_users_cannot_access_activity_logs(): void
    {
        $log = $this->createAuditLog(
            $this->createUser('Operator', 'operator'),
            'login',
            'Login berhasil',
        );

        $this->get(route('admin.activity-log.index'))
            ->assertRedirect(route('login'));

        $user = $this->createUser('Regular User', 'regular-user');
        $this->actingAs($user)
            ->get(route('admin.activity-log.index'))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('admin.activity-log.show', $log))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('admin.activity-log.export', ['format' => 'csv']))
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('admin.activity-log.destroy-all'))
            ->assertForbidden();
    }

    public function test_destroy_all_deletes_all_activity_logs(): void
    {
        $admin = $this->createSuperAdmin();
        $this->createAuditLog($admin, 'login', 'Login berhasil');
        $this->createAuditLog($admin, 'updated', 'Mengubah data gudang');

        $this->assertSame(2, AuditLog::count());

        $response = $this->actingAs($admin)
            ->delete(route('admin.activity-log.destroy-all'));

        $response->assertRedirect(route('admin.activity-log.index'))
            ->assertSessionHas('success', 'Semua catatan log aktivitas berhasil dihapus.');

        $this->assertSame(0, AuditLog::count());
    }

    private function createSuperAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = $this->createUser('Super Admin', 'super-admin');
        $admin->assignRole($role);

        return $admin;
    }

    private function createUser(string $name, string $username): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'name' => $name,
            'username' => $username,
            'is_aktif' => true,
        ]));
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    private function createAuditLog(
        User $actor,
        string $action,
        string $description,
        ?Carbon $createdAt = null,
        ?array $changes = null,
        string $subjectType = User::class,
    ): AuditLog {
        return AuditLog::forceCreate([
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $actor->id,
            'description' => $description,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Test browser agent',
            'changes' => $changes,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
