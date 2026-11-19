<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('rejectedLoginCases')]
    public function test_login_rejects_invalid_or_inactive_accounts_without_disclosing_status(
        bool $isActive,
        string $password,
    ): void {
        $user = User::factory()->create(['is_aktif' => $isActive]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => $password,
        ])
            ->assertSessionHasErrors([
                'username' => 'Username atau kata sandi yang Anda masukkan tidak sesuai.',
            ]);

        $this->assertGuest();
    }

    /**
     * @return array<string, array{bool, string}>
     */
    public static function rejectedLoginCases(): array
    {
        return [
            'inactive account with correct password' => [false, 'password'],
            'active account with incorrect password' => [true, 'incorrect-password'],
        ];
    }

    public function test_logout_invalidates_the_authenticated_session_and_records_activity(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('pending-access'));

        $response = $this->post(route('logout'));

        $response
            ->assertRedirect(route('welcome'))
            ->assertSessionMissing('user_logged_in_id');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'causer_id' => $user->id,
            'causer_type' => User::class,
            'action' => 'logout',
        ]);
    }

    public function test_deactivated_account_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('pending-access'));

        $user->update(['is_aktif' => false]);

        $response = $this->get(route('pending-access'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionMissing('user_logged_in_id');

        $this->assertGuest();
    }

    public function test_login_throttle_ignores_untrusted_forwarded_ip_headers(): void
    {
        $username = 'proxy-check';
        RateLimiter::clear($username.'|127.0.0.1');

        $this->withHeader('X-Forwarded-For', '203.0.113.99')
            ->post(route('login'), [
                'username' => $username,
                'password' => 'incorrect-password',
            ]);

        $this->assertSame(1, RateLimiter::attempts($username.'|127.0.0.1'));
    }

    public function test_login_uses_current_role_dashboard_instead_of_stale_intended_warehouse_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]));

        $this->withSession([
            'url.intended' => 'http://localhost/unit-bisnis/stok?gudang_id=12&status=kritis',
            'active_gudang_id' => 12,
        ])
            ->post(route('login'), [
                'username' => $user->username,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('active_gudang_id')
            ->assertSessionMissing('url.intended');
    }
}
