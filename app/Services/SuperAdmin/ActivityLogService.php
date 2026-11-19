<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityLogService
{
    /**
     * Delete all activity log records.
     */
    public function clearAll(): int
    {
        return AuditLog::query()->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     logs: LengthAwarePaginator,
     *     actions: Collection,
     *     subjects: Collection,
     *     operators: \Illuminate\Database\Eloquent\Collection,
     *     stats: array{total_today: int, authentication_events: int, data_mutations: int, active_actors: int}
     * }
     */
    public function getListingData(array $filters): array
    {
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->limit(50)
            ->pluck('action');
        $subjects = AuditLog::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->limit(50)
            ->pluck('subject_type');
        $operatorIds = AuditLog::query()
            ->where('causer_type', User::class)
            ->select('causer_id')
            ->distinct();
        $operators = User::query()
            ->select(['id', 'name', 'username'])
            ->with('roles:id,name,guard_name')
            ->whereIn('id', $operatorIds)
            ->orderBy('name')
            ->limit(100)
            ->get();

        if (isset($filters['operator']) && ! $operators->contains('id', (int) $filters['operator'])) {
            $selectedOperator = User::query()
                ->select(['id', 'name', 'username'])
                ->with('roles:id,name,guard_name')
                ->find($filters['operator']);

            if ($selectedOperator !== null) {
                $operators->prepend($selectedOperator);
            }
        }

        return [
            'logs' => $this->filteredLogs($filters)
                ->with($this->causerEagerLoads())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->appends($filters),
            'actions' => $actions,
            'subjects' => $subjects,
            'operators' => $operators,
            'stats' => $this->dailyMetrics(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adjacentLog(AuditLog $auditLog, string $direction, array $filters): ?AuditLog
    {
        $query = $this->filteredLogs($filters, includeChanges: true);
        $currentDate = $auditLog->created_at;

        if ($currentDate === null) {
            if ($direction === 'previous') {
                return $query->whereNull('created_at')
                    ->where('id', '>', $auditLog->getKey())
                    ->orderBy('id')
                    ->first();
            }

            return $query
                ->where(static function (Builder $matching) use ($auditLog): void {
                    $matching->where(static function (Builder $nullTimestamps) use ($auditLog): void {
                        $nullTimestamps->whereNull('created_at')
                            ->where('id', '<', $auditLog->getKey());
                    })->orWhereNotNull('created_at');
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
        }

        if ($direction === 'previous') {
            $neighbor = $query
                ->where(static function (Builder $matching) use ($auditLog): void {
                    $matching->where('created_at', '>', $auditLog->created_at)
                        ->orWhere(static function (Builder $sameTimestamp) use ($auditLog): void {
                            $sameTimestamp->where('created_at', $auditLog->created_at)
                                ->where('id', '>', $auditLog->getKey());
                        });
                })
                ->orderBy('created_at')
                ->orderBy('id')
                ->first();

            if ($neighbor !== null) {
                return $neighbor;
            }

            return $this->filteredLogs($filters, includeChanges: true)
                ->whereNull('created_at')
                ->orderBy('id')
                ->first();
        }

        return $query
            ->where(static function (Builder $matching) use ($auditLog): void {
                $matching->where('created_at', '<', $auditLog->created_at)
                    ->orWhere(static function (Builder $sameTimestamp) use ($auditLog): void {
                        $sameTimestamp->where('created_at', $auditLog->created_at)
                            ->where('id', '<', $auditLog->getKey());
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{log: array<string, mixed>, actor: array<string, mixed>}
     */
    public function detailData(AuditLog $auditLog): array
    {
        $auditLog->load('causer');
        $actor = $auditLog->causer;

        if ($actor instanceof User) {
            $actor->load('roles');
        }

        $transactionDataHidden = self::shouldHideTransactionData($auditLog);

        return [
            'log' => [
                'id' => $auditLog->getKey(),
                'recorded_at' => $auditLog->created_at?->toIso8601String(),
                'action' => $auditLog->action,
                'action_label' => self::getActionLabel($auditLog->action),
                'subject' => [
                    'type' => class_basename((string) $auditLog->subject_type),
                    'id' => $auditLog->subject_id,
                ],
                'description' => $transactionDataHidden ? 'Data transaksi disembunyikan' : $auditLog->description,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'transaction_data_hidden' => $transactionDataHidden,
                'changes' => $transactionDataHidden ? null : $this->redactSensitiveChanges($auditLog->changes),
            ],
            'actor' => [
                'name' => $actor?->name ?? ($auditLog->causer_type === User::class ? 'Pengguna tidak tersedia' : 'Sistem'),
                'username' => $actor?->username,
                'roles' => $actor instanceof User ? $actor->roles->pluck('name')->values() : [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    public function filteredLogs(array $filters, bool $includeChanges = false): Builder
    {
        $columns = [
            'id',
            'causer_type',
            'causer_id',
            'action',
            'subject_type',
            'subject_id',
            'description',
            'ip_address',
            'created_at',
        ];

        if ($includeChanges) {
            $columns[] = 'changes';
            $columns[] = 'user_agent';
        }

        $query = AuditLog::query()->select($columns);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<', Carbon::parse($filters['date_to'])->startOfDay()->addDay());
        }

        if (isset($filters['operator'])) {
            $query->where('causer_type', User::class)->where('causer_id', $filters['operator']);
        }

        if (($filters['action'] ?? '') !== '') {
            $query->where('action', $filters['action']);
        }

        if (($filters['subject'] ?? '') !== '') {
            $query->where('subject_type', $filters['subject']);
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $pattern = '%'.$search.'%';

            $query->where(static function (Builder $matching) use ($operator, $pattern): void {
                $matching->where('action', $operator, $pattern)
                    ->orWhere('subject_type', $operator, $pattern)
                    ->orWhere('description', $operator, $pattern)
                    ->orWhere('ip_address', $operator, $pattern)
                    ->orWhereRaw('CAST(subject_id AS TEXT) LIKE ?', [$pattern])
                    ->orWhereHasMorph('causer', [User::class], static function (Builder $causer) use ($operator, $pattern): void {
                        $causer->where('name', $operator, $pattern)
                            ->orWhere('username', $operator, $pattern);
                    });
            });
        }

        return $query;
    }

    public static function shouldHideTransactionData(AuditLog $auditLog): bool
    {
        $action = mb_strtolower($auditLog->action);
        $subjectType = mb_strtolower(class_basename((string) $auditLog->subject_type));

        if (in_array($action, ['input_penerimaan', 'finalisasi'], true)) {
            return true;
        }

        foreach ([
            'penerimaanbarang',
            'pengeluaranbarang',
            'penerimaanpemindahanunit',
            'returbarang',
            'stokawal',
            'stokopname',
            'transferbarang',
        ] as $transactionType) {
            if (str_contains($subjectType, $transactionType)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<mixed>|null */
    public function redactSensitiveChanges(mixed $changes): ?array
    {
        if (! is_array($changes)) {
            return null;
        }

        $safeChanges = [];
        foreach ($changes as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|session|credential|authorization/i', $key) === 1) {
                continue;
            }

            $safeChanges[$key] = is_array($value) ? $this->redactSensitiveChanges($value) : $value;
        }

        return $safeChanges;
    }

    /** @return array<string, callable> */
    public function causerEagerLoads(): array
    {
        return [
            'causer' => static function (MorphTo $morphTo): void {
                $morphTo->constrain([
                    User::class => static fn (Builder $query): Builder => $query->select(['id', 'name', 'username']),
                ]);
                $morphTo->morphWith([User::class => ['roles']]);
            },
        ];
    }

    /** @return array<string, mixed> */
    public function exportRow(AuditLog $log): array
    {
        $actor = $log->causer;
        $hidden = self::shouldHideTransactionData($log);

        return [
            'id' => (int) $log->getKey(),
            'recorded_at' => $log->created_at?->toIso8601String() ?? '',
            'actor_name' => $actor?->name ?? ($log->causer_type === User::class ? 'Pengguna tidak tersedia' : 'Sistem'),
            'actor_username' => $actor?->username ?? '',
            'action' => $log->action,
            'subject_type' => class_basename((string) $log->subject_type),
            'subject_id' => (int) $log->subject_id,
            'description' => $hidden ? 'Data transaksi disembunyikan' : ($log->description ?? ''),
            'ip_address' => $log->ip_address ?? '',
            'user_agent' => $log->user_agent ?? '',
            'changes' => $hidden ? null : $this->redactSensitiveChanges($log->changes),
        ];
    }

    public function csvSafe(int|string $value): int|string
    {
        if (is_string($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    public static function getActionBadge(string $action): string
    {
        return match (mb_strtolower($action)) {
            'login', 'login_success', 'created', 'create_user' => 'activity-badge--primary',
            'logout' => 'activity-badge--neutral',
            'updated', 'update_role', 'update_role_matrix', 'reset_password', 'password_reset', 'reset_user_password' => 'activity-badge--warning',
            'deleted', 'login_failed', 'failed_login', 'login.failure' => 'activity-badge--danger',
            'finalisasi' => 'activity-badge--neutral',
            default => 'activity-badge--tertiary',
        };
    }

    public static function getActionLabel(string $action): string
    {
        return match (mb_strtoupper($action)) {
            'LOGIN', 'LOGIN_SUCCESS' => 'LOGIN',
            'LOGIN_FAILED', 'FAILED_LOGIN', 'LOGIN.FAILURE' => 'LOGIN GAGAL',
            'LOGOUT' => 'LOGOUT',
            'CREATED' => 'BUAT DATA',
            'UPDATED' => 'PERBARUI DATA',
            'DELETED' => 'HAPUS DATA',
            'UPDATE_ROLE' => 'UBAH ROLE',
            'UPDATE_ROLE_MATRIX' => 'UBAH PERMISSION',
            'CREATE_USER' => 'BUAT PENGGUNA',
            'RESET_PASSWORD', 'PASSWORD_RESET', 'RESET_USER_PASSWORD' => 'RESET PASSWORD',
            'INPUT_PENERIMAAN' => 'INPUT PENERIMAAN',
            'FINALISASI' => 'FINALISASI OPNAME',
            default => mb_strtoupper(str_replace('_', ' ', $action)),
        };
    }

    /**
     * @return array{total_today: int, authentication_events: int, data_mutations: int, active_actors: int}
     */
    private function dailyMetrics(): array
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $metrics = AuditLog::query()
            ->where('created_at', '>=', $today)
            ->where('created_at', '<', $tomorrow)
            ->selectRaw('COUNT(*) AS total_today')
            ->selectRaw("SUM(CASE WHEN action IN ('login', 'logout') THEN 1 ELSE 0 END) AS authentication_events")
            ->selectRaw("SUM(CASE WHEN action IN ('created', 'updated', 'deleted', 'CREATE_USER', 'UPDATE_ROLE', 'UPDATE_ROLE_MATRIX', 'INPUT_PENERIMAAN', 'FINALISASI') THEN 1 ELSE 0 END) AS data_mutations")
            ->selectRaw('COUNT(DISTINCT causer_id) AS active_actors')
            ->first();

        return [
            'total_today' => (int) $metrics->total_today,
            'authentication_events' => (int) $metrics->authentication_events,
            'data_mutations' => (int) $metrics->data_mutations,
            'active_actors' => (int) $metrics->active_actors,
        ];
    }
}
