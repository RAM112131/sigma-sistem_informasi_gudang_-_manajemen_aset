<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $gudangs = Gudang::all();
        $gudangStore = Gudang::where('kode', 'STORE')->first();
        $gudangSukahati = Gudang::where('kode', 'SUKAHATI')->first();

        // 1. Super Admin
        $superUser = User::withTrashed()->updateOrCreate(
            ['username' => 'superadmin'],
            ['name' => 'Super Admin', 'password' => 'password', 'is_aktif' => true, 'deleted_at' => null]
        );
        $superUser->syncRoles([Role::findByName('Super Admin', 'web')]);
        if ($gudangs->isNotEmpty()) {
            $superUser->gudang()->sync($gudangs->pluck('id'));
        }

        // 2. Admin Gudang Store (Armada)
        $adminStore = User::withTrashed()->updateOrCreate(
            ['username' => 'adminstore'],
            ['name' => 'Admin Store', 'password' => 'password', 'is_aktif' => true, 'deleted_at' => null]
        );
        $adminStore->syncRoles([Role::findByName('Admin Gudang Store', 'web')]);
        if ($gudangStore) {
            $adminStore->gudang()->sync([$gudangStore->id]);
        }

        // 3. Admin Gudang Umum (RPA Sukahati)
        $adminGU = User::withTrashed()->updateOrCreate(
            ['username' => 'admingu'],
            ['name' => 'Admin GU Sukahati', 'password' => 'password', 'is_aktif' => true, 'deleted_at' => null]
        );
        $adminGU->syncRoles([Role::findByName('Admin Gudang Umum', 'web')]);
        if ($gudangSukahati) {
            $adminGU->gudang()->sync([$gudangSukahati->id]);
        }

        // 4. Recording Gudang Umum (RPA Sukahati)
        $recordingGU = User::withTrashed()->updateOrCreate(
            ['username' => 'recordinggu'],
            ['name' => 'Recording GU Sukahati', 'password' => 'password', 'is_aktif' => true, 'deleted_at' => null]
        );
        $recordingGU->syncRoles([Role::findByName('Recording Gudang Umum', 'web')]);
        if ($gudangSukahati) {
            $recordingGU->gudang()->sync([$gudangSukahati->id]);
        }
    }
}
