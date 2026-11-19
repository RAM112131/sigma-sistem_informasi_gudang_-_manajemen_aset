<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreUserRequest;
use App\Http\Requests\SuperAdmin\UpdateUserRequest;
use App\Models\User;
use App\Services\SuperAdmin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Display a listing of system user accounts.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'gudang' => ['nullable', 'integer', 'exists:gudangs,id'],
            'status' => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $data = $this->userService->getListingData($filters);

        $viewName = view()->exists('super-admin.users.index') ? 'super-admin.users.index' : 'admin.users.index';

        return view($viewName, $data);
    }

    /**
     * Display the user creation form.
     */
    public function create(): View
    {
        return $this->formView();
    }

    /**
     * Display the user editing form.
     */
    public function edit(User $user): View
    {
        return $this->formView($user);
    }

    /**
     * Store a newly created system user account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update the specified system user account.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified system user account.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (! $this->userService->delete($user, (int) auth()->id())) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Helper to render create and edit form views consistently.
     */
    private function formView(?User $user = null): View
    {
        $viewName = view()->exists('super-admin.users.form') ? 'super-admin.users.form' : 'admin.users.form';

        return view($viewName, $this->userService->getFormData($user, session()->getOldInput()));
    }
}
