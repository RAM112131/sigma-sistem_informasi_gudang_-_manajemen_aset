<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Jobs\GenerateVectorEmbedding;
use App\Models\Gudang;
use App\Models\User;
use App\Services\SemanticVectorSearch;
use App\Support\SemanticVectorSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(
        private readonly SemanticVectorSearch $semanticSearch,
    ) {}

    /**
     * @param  array{search?: ?string, role?: ?string, gudang?: int|string|null, status?: ?string, per_page?: int|string|null}  $filters
     * @return array<string, mixed>
     */
    public function getListingData(array $filters): array
    {
        $search = trim($filters['search'] ?? '');
        $perPage = (int) ($filters['per_page'] ?? 10);
        $sessionCount = null;

        if (config('session.driver') === 'database') {
            $sessionCount = DB::table((string) config('session.table'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
                ->distinct()
                ->count('user_id');
        }

        $topGudang = Gudang::query()
            ->withCount('users')
            ->orderByDesc('users_count')
            ->orderBy('nama')
            ->first();

        $query = User::query()
            ->with([
                'roles:id,name,guard_name',
                'gudang:id,nama,jenis',
            ]);

        if ($search !== '') {
            $this->semanticSearch->apply($query, 'users', $search, 'search');
        }

        $users = $query
            ->when($filters['role'] ?? null, static function (Builder $query, string $role): void {
                $query->whereHas('roles', static fn (Builder $roles): Builder => $roles->where('name', $role));
            })
            ->when($filters['gudang'] ?? null, static function (Builder $query, int|string $gudangId): void {
                $query->whereHas('gudang', static fn (Builder $warehouses): Builder => $warehouses->whereKey((int) $gudangId));
            })
            ->when($filters['status'] ?? null, static function (Builder $query, string $status): void {
                $query->where('is_aktif', $status === 'active');
            })
            ->when(empty($query->getQuery()->orders), static function (Builder $query): void {
                $query->orderBy('name');
            }, static function (Builder $query): void {
                $query->orderBy('name');
            })
            ->paginate($perPage)
            ->withQueryString();

        return [
            'users' => $users,
            'availableRoles' => Role::query()->orderBy('name')->pluck('name'),
            'availableGudangs' => Gudang::query()->orderBy('nama')->get(['id', 'nama', 'jenis']),
            'stats' => [
                'total_users' => User::count(),
                'active_users' => User::where('is_aktif', true)->count(),
                'inactive_users' => User::where('is_aktif', false)->count(),
                'multi_role_users' => User::has('roles', '>', 1)->count(),
                'top_gudang' => $topGudang,
                'active_sessions' => $sessionCount,
            ],
            'filters' => $filters,
            'perPage' => $perPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldInput
     * @return array<string, mixed>
     */
    public function getFormData(?User $user, array $oldInput = []): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();
        $user?->loadMissing(['roles', 'gudang']);

        $rawSelectedRoles = $oldInput['roles'] ?? ($user?->roles->pluck('name')->all() ?? []);
        $selectedRoles = is_array($rawSelectedRoles)
            ? array_map(static fn (mixed $role): string => (string) $role, $rawSelectedRoles)
            : [];
        $rawSelectedGudangIds = $oldInput['gudang_ids'] ?? ($user?->gudang->pluck('id')->all() ?? []);
        $selectedGudangIds = is_array($rawSelectedGudangIds)
            ? array_map(static fn (mixed $id): string => (string) $id, $rawSelectedGudangIds)
            : [];
        $warehouses = Gudang::query()->orderBy('nama')->get(['id', 'kode', 'nama', 'jenis', 'is_aktif']);
        $roleOptions = $roles
            ->map(static fn (Role $role): array => [
                'name' => $role->name,
                'description' => $role->description,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ])
            ->values()
            ->all();
        $isActive = (string) ($oldInput['is_aktif'] ?? ($user?->is_aktif === false ? '0' : '1')) === '1';

        return [
            'user' => $user,
            'roles' => $roles,
            'warehouses' => $warehouses,
            'selectedRoles' => $selectedRoles,
            'selectedGudangIds' => $selectedGudangIds,
            'isActive' => $isActive,
            'formState' => [
                'name' => $oldInput['name'] ?? ($user?->name ?? ''),
                'username' => $oldInput['username'] ?? ($user?->username ?? ''),
                'isActive' => $isActive,
                'roles' => $selectedRoles,
                'gudangIds' => $selectedGudangIds,
                'roleOptions' => $roleOptions,
                'warehouseOptions' => $warehouses->map(static fn (Gudang $gudang): array => [
                    'id' => (string) $gudang->id,
                    'name' => $gudang->nama,
                    'code' => $gudang->kode,
                ])->all(),
            ],
        ];
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return DB::transaction(static function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                'username' => $attributes['username'],
                'password' => $attributes['password'],
                'is_aktif' => $attributes['is_aktif'],
            ]);

            $user->syncRoles($attributes['roles']);
            $user->gudang()->sync($attributes['gudang_ids'] ?? []);

            if (SemanticVectorSchema::hasVectorColumn('users')) {
                $user->load(['roles', 'gudang']);
                GenerateVectorEmbedding::dispatch($user, $user->getSemanticText())->afterCommit();
            }

            return $user;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User
    {
        return DB::transaction(static function () use ($user, $attributes): User {
            $user->name = $attributes['name'];
            $user->username = $attributes['username'];
            $user->is_aktif = $attributes['is_aktif'];

            if (! empty($attributes['password'])) {
                $user->password = $attributes['password'];
            }

            $user->save();
            $user->syncRoles($attributes['roles']);
            $user->gudang()->sync($attributes['gudang_ids'] ?? []);

            if (SemanticVectorSchema::hasVectorColumn('users')) {
                $user->load(['roles', 'gudang']);
                GenerateVectorEmbedding::dispatch($user, $user->getSemanticText())->afterCommit();
            }

            return $user;
        });
    }

    public function delete(User $user, int $currentUserId): bool
    {
        if ((int) $user->getKey() === $currentUserId) {
            return false;
        }

        return DB::transaction(static fn (): bool => (bool) $user->delete());
    }
}
