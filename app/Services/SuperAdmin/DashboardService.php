<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\AuditLog;
use App\Models\Gudang;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class DashboardService
{
    private const DEFAULT_ROLE_NAMES = [
        'Super Admin',
        'super_admin',
        'Admin Gudang Store',
        'admin_gudang_store',
        'Admin Gudang Umum',
        'admin_gudang_umum',
        'Recording Gudang Umum',
        'recording_gudang_umum',
    ];

    private const STORE_WAREHOUSE_TYPES = ['holding', 'global_master', 'gudang_store'];

    private const BUSINESS_WAREHOUSE_TYPES = ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'];

    /**
     * @param  array{search?: ?string, role?: ?string, status?: ?string}  $filters
     * @return array{
     *     stats: array<string, int>,
     *     recentUsers: LengthAwarePaginator,
     *     availableRoles: Collection,
     *     recentSystemLogs: Collection
     * }
     */
    public function getDashboardData(array $filters): array
    {
        $totalRoles = Role::count();
        $defaultRoles = Role::whereIn('name', self::DEFAULT_ROLE_NAMES)->count();
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_aktif', true)->count(),
            'total_roles' => $totalRoles,
            'default_roles' => $defaultRoles,
            'custom_roles' => $totalRoles - $defaultRoles,
            'active_gudangs' => Gudang::where('is_aktif', true)->count(),
            'active_store_gudangs' => Gudang::where('is_aktif', true)
                ->whereIn('jenis', self::STORE_WAREHOUSE_TYPES)
                ->count(),
            'active_business_gudangs' => Gudang::where('is_aktif', true)
                ->whereIn('jenis', self::BUSINESS_WAREHOUSE_TYPES)
                ->count(),
            'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
        ];

        $recentUsers = User::query()
            ->with([
                'roles',
                'gudang' => static function (BelongsToMany $query): void {
                    $query->where('is_aktif', true);
                },
            ])
            ->when($filters['search'] ?? null, static function (Builder $query, string $search): void {
                $query->where(static function (Builder $matching) use ($search): void {
                    $matching->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['role'] ?? null, static function (Builder $query, string $role): void {
                $query->whereHas('roles', static fn (Builder $roles): Builder => $roles->where('name', $role));
            })
            ->when($filters['status'] ?? null, static function (Builder $query, string $status): void {
                $query->where('is_aktif', $status === 'active');
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(6)
            ->withQueryString();

        $recentSystemLogs = AuditLog::with(['causer.roles', 'subject'])
            ->whereIn('subject_type', [User::class, Gudang::class])
            ->where('causer_type', User::class)
            ->whereIn('action', ['created', 'updated', 'deleted', 'login', 'logout'])
            ->latest()
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(static fn (AuditLog $log): array => [
                'actor' => $log->causer?->name ?? 'Sistem',
                'role' => $log->causer?->getRoleNames()->implode(', ') ?: 'Pengguna',
                'action' => match ([$log->subject_type, $log->action]) {
                    [User::class, 'created'] => 'USER BARU',
                    [User::class, 'updated'] => 'UPDATE USER',
                    [User::class, 'deleted'] => 'HAPUS USER',
                    [Gudang::class, 'created'] => 'GUDANG BARU',
                    [Gudang::class, 'updated'] => 'UPDATE GUDANG',
                    [Gudang::class, 'deleted'] => 'HAPUS GUDANG',
                    [User::class, 'login'] => 'LOGIN',
                    [User::class, 'logout'] => 'LOGOUT',
                    default => strtoupper($log->action),
                },
                'badge' => match ($log->action) {
                    'created', 'login' => 'success',
                    'updated' => 'brand',
                    'deleted' => 'critical',
                    default => 'neutral',
                },
                'subject' => $log->subject instanceof User ? $log->subject->name : $log->subject?->nama,
                'description' => $log->description ?? 'Aktivitas '.$log->action,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ]);

        return [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'availableRoles' => Role::query()->orderBy('name')->pluck('name'),
            'recentSystemLogs' => $recentSystemLogs,
        ];
    }
}
