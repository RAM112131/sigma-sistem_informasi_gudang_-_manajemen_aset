<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreRoleRequest;
use App\Http\Requests\SuperAdmin\UpdateRoleRequest;
use App\Services\SuperAdmin\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    /**
     * Show the role creation form.
     */
    public function create(): View
    {
        return view('super-admin.roles.create', $this->roleService->getFormData());
    }

    /**
     * Show the role permission editor.
     */
    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'web', 404);

        return view('super-admin.roles.create', [
            ...$this->roleService->getFormData($role),
            'role' => $role,
            'isProtectedRole' => in_array($role->name, ['Super Admin', 'super_admin'], true),
        ]);
    }

    /**
     * Display Spatie RBAC roles and permission matrix.
     */
    public function index(): View
    {
        return view('super-admin.roles.index', $this->roleService->getListingData());
    }

    /**
     * Store a newly created custom role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated());

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' berhasil dibuat!");
    }

    /**
     * Update the matrix permissions for a specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        if (! $this->roleService->update($role, $request->validated())) {
            return redirect()->route('admin.roles.index')->with('error', 'Role Super Admin dilindungi oleh sistem. Akses penuh selalu aktif secara permanen dan tidak dapat diubah!');
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' berhasil diperbarui!");
    }

    /**
     * Remove the specified role from database.
     */
    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        $result = $this->roleService->delete($role);

        if (! $result['deleted']) {
            return redirect()->route('admin.roles.index')->with('error', $result['reason']);
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$result['name']}' berhasil dihapus!");
    }

    /**
     * Clear the Spatie permission cache.
     */
    public function syncCache(PermissionRegistrar $permissionRegistrar): RedirectResponse
    {
        $this->roleService->clearPermissionCache($permissionRegistrar);

        return redirect()->route('admin.roles.index')->with('success', 'Cache permission berhasil disinkronisasi.');
    }
}
