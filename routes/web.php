<?php

declare(strict_types=1);

use App\Http\Controllers\Api\QuickAddController;
use App\Http\Controllers\MasterData\DivisiPemintaController;
use App\Http\Controllers\MasterData\KategoriController;
use App\Http\Controllers\MasterData\MasterBarangController;
use App\Http\Controllers\MasterData\SatuanController;
use App\Http\Controllers\Portal\WelcomeController;
use App\Http\Controllers\Store\DashboardStoreController;
use App\Http\Controllers\Store\LaporanExportController;
use App\Http\Controllers\Store\LaporanStoreController;
use App\Http\Controllers\Store\PenerimaanStoreController;
use App\Http\Controllers\Store\PengeluaranStoreController;
use App\Http\Controllers\Store\StokStoreController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\DashboardController as AdminDashboardController;
use App\Http\Controllers\SuperAdmin\GudangController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\DashboardController as AdminGUDashboardController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\PemindahanController as AdminGUPemindahanController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\PermintaanBarangController as AdminGUPermintaanBarangController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\RekonsiliasiPenerimaanController as AdminGURekonsiliasiPenerimaanController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\RekonsiliasiPengeluaranController as AdminGURekonsiliasiPengeluaranController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\ReportController as AdminGUReportController;
use App\Http\Controllers\UnitBisnis\AdminGudangUmum\StokOpnameController as AdminGUStokOpnameController;
use App\Http\Controllers\UnitBisnis\RecordingGudangUmum\DashboardController as RecordingGUDashboardController;
use App\Http\Controllers\UnitBisnis\RecordingGudangUmum\PenerimaanController as RecordingGUPenerimaanController;
use App\Http\Controllers\UnitBisnis\RecordingGudangUmum\PengeluaranController as RecordingGUPengeluaranController;
use App\Http\Controllers\UnitBisnis\StokController as UnitBisnisStokController;
use App\Http\Middleware\LogReadActivity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Dev Migration Helper Route (Local & Super Admin Only)
if (app()->environment('local', 'testing')) {
    Route::middleware(['auth', 'role:Super Admin|super_admin'])->get('/dev-migrate', function () {
        Artisan::call('migrate', ['--force' => true]);

        // Ensure revised Spatie permissions exist
        $perms = [
            'barang.view',
            'barang.create',
            'barang.manage',
            'kategori.create',
            'kategori.manage',
            'satuan.create',
            'satuan.manage',
            'area.read',
            'area.create',
            'area.manage',
            'stok_awal.import',
            'pemindahan.terima',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Assign full permissions to Super Admin role
        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions(Permission::all());

        $adminStore = Role::findOrCreate('Admin Gudang Store', 'web');
        $adminStore->syncPermissions(['barang.view', 'barang.create', 'kategori.create', 'satuan.create', 'stok_awal.import']);

        $adminGU = Role::findOrCreate('Admin Gudang Umum', 'web');
        $adminGU->syncPermissions([
            'barang.view',
            'barang.create',
            'kategori.create',
            'satuan.create',
            'area.read',
            'area.create',
            'area.manage',
            'pemindahan.terima',
        ]);

        return 'Migration & Permissions Seeded: '.Artisan::output();
    });
}

// Public Welcome Page
Route::get('/', WelcomeController::class)->name('welcome');

// Auth Routes (Login, Logout)
require __DIR__.'/auth.php';

// Authenticated Main Routes
Route::middleware('auth')->group(function () {
    // Halaman Informasi Akses Resmi (Pending Config / Unconfigured Access)
    Route::get('/pending-access', function () {
        return view('errors.pending-access');
    })->name('pending-access');

    // =========================================================================
    // 1. ROLE: SUPER ADMIN (PT Taman Hati Group Holding)
    // =========================================================================
    Route::prefix('admin')->name('admin.')->middleware(['role:Super Admin|super_admin'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });
        Route::get('/dashboard', AdminDashboardController::class)
            ->middleware(LogReadActivity::class)
            ->name('dashboard');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Role & Permission Matrix
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/roles/cache/sync', [RoleController::class, 'syncCache'])->name('roles.cache-sync');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Master Gudang
        Route::get('/gudang', [GudangController::class, 'index'])->name('gudang.index');
        Route::get('/gudang/create', [GudangController::class, 'create'])->name('gudang.create');
        Route::post('/gudang', [GudangController::class, 'store'])->name('gudang.store');
        Route::get('/gudang/{gudang}/edit', [GudangController::class, 'edit'])->name('gudang.edit');
        Route::put('/gudang/{gudang}', [GudangController::class, 'update'])->name('gudang.update');
        Route::delete('/gudang/{gudang}', [GudangController::class, 'destroy'])->name('gudang.destroy');

        // Activity Audit Log
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
        Route::delete('/activity-log', [ActivityLogController::class, 'destroyAll'])->name('activity-log.destroy-all');
        Route::get('/activity-log/{auditLog}', [ActivityLogController::class, 'show'])->name('activity-log.show');

    });

    // =========================================================================
    // 2. ROLE: ADMIN GUDANG STORE (Armada / Sentral)
    // =========================================================================
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/dashboard', DashboardStoreController::class)
            ->middleware(LogReadActivity::class)
            ->name('dashboard');

        // Master Barang (Katalog Master - Read-Only Browse for Admin Store, Manage for Super Admin)
        Route::get('/katalog', fn () => redirect()->route('master.barang.index'))->name('katalog.index');

        // Stok Gudang Store, Kartu Stok, & Import Data Awal
        Route::middleware('permission:store_stok.read|store.view|store.read')->group(function () {
            Route::get('/stok', [StokStoreController::class, 'index'])->name('stok.index');
            Route::get('/stok/{barang}/kartu', [StokStoreController::class, 'kartuStok'])->name('stok.kartu');
        });
        Route::middleware('permission:store_stok.create|stok_awal.import')->group(function () {
            Route::get('/stok/import-template', [StokStoreController::class, 'downloadImportTemplate'])->name('stok.import-template');
            Route::post('/stok/import-analyze', [StokStoreController::class, 'analyzeImport'])->name('stok.import-analyze');
            Route::post('/stok/import-process', [StokStoreController::class, 'processImport'])->name('stok.import-process');
        });

        // Penerimaan Barang Supplier
        Route::middleware('permission:store_penerimaan.read|store_penerimaan.create|store.view|store.read|store.penerimaan')->group(function () {
            Route::get('/penerimaan', [PenerimaanStoreController::class, 'index'])->name('penerimaan.index');
        });
        Route::middleware('permission:store_penerimaan.create|store.penerimaan|store.create')->group(function () {
            Route::post('/penerimaan', [PenerimaanStoreController::class, 'store'])->name('penerimaan.store');
        });

        // Permintaan Barang (incoming requests from unit bisnis)
        Route::middleware('permission:store_pengeluaran.read|store_pengeluaran.create|store.distribusi')->group(function () {
            Route::get('/permintaan', [PengeluaranStoreController::class, 'permintaanIndex'])->name('permintaan.index');
        });

        // Pengeluaran Barang: incoming requests and transfer dispatch are one workflow.
        Route::middleware('permission:store_pengeluaran.read|store_pengeluaran.create|store.distribusi')->group(function () {
            Route::get('/pengeluaran', [PengeluaranStoreController::class, 'index'])->name('pengeluaran.index');
            Route::get('/pengeluaran/barang-search', [PengeluaranStoreController::class, 'cariBarangPemindahan'])->name('pengeluaran.barang-search');
        });
        Route::middleware('permission:store_pengeluaran.create|store.distribusi')->group(function () {
            Route::post('/pengeluaran/pemindahan', [PengeluaranStoreController::class, 'buatPemindahanLangsung'])->name('pengeluaran.pemindahan.store');
            Route::post('/pengeluaran/permintaan/{permintaan}/proses', [PengeluaranStoreController::class, 'prosesPermintaan'])->name('pengeluaran.permintaan.proses');
        });
        Route::middleware('permission:store_pengeluaran.update|store.distribusi')->group(function () {
            Route::post('/pengeluaran/permintaan/{permintaan}/tolak', [PengeluaranStoreController::class, 'tolakPermintaan'])->name('pengeluaran.permintaan.tolak');
            Route::post('/pengeluaran/pemindahan/{transfer}/kirim', [PengeluaranStoreController::class, 'konfirmasiKirim'])->name('pengeluaran.pemindahan.kirim');
        });

        // Laporan Stok Store
        Route::get('/laporan', [LaporanStoreController::class, 'index'])->name('laporan.index');
        Route::middleware('permission:store_laporan.export|laporan.export')->group(function () {
            Route::get('/laporan/export/excel', [LaporanStoreController::class, 'exportExcel'])->name('laporan.export.excel');
            Route::get('/laporan/export/pdf', [LaporanStoreController::class, 'exportPdf'])->name('laporan.export.pdf');
            Route::get('/laporan/{jenis}/preview', [LaporanExportController::class, 'preview'])
                ->whereIn('jenis', ['stok', 'barang_masuk', 'barang_keluar', 'pembelian_tunai_transfer'])
                ->name('laporan.export.preview');
            Route::get('/laporan/{jenis}/export', [LaporanExportController::class, 'export'])
                ->whereIn('jenis', ['stok', 'barang_masuk', 'barang_keluar', 'pembelian_tunai_transfer'])
                ->name('laporan.export.generate');
        });
    });

    // =========================================================================
    // 3. ROLE: UNIT BISNIS (Admin Gudang Umum & Recording Gudang Umum)
    // =========================================================================
    Route::prefix('unit-bisnis')->name('unit-bisnis.')->group(function () {
        // 3.1. SUB-ROLE: ADMIN GUDANG UMUM (Manajerial & Verifikasi Unit)
        Route::prefix('admin-gu')->name('admin-gu.')->group(function () {
            Route::get('/dashboard', AdminGUDashboardController::class)
                ->middleware(['role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin', LogReadActivity::class])
                ->name('dashboard');

            Route::middleware('role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->prefix('laporan')
                ->name('laporan.')
                ->group(function (): void {
                    Route::get('/', [AdminGUReportController::class, 'index'])->name('index');
                    Route::get('/export-excel', [AdminGUReportController::class, 'exportExcel'])->name('export-excel');
                    Route::get('/export-pdf', [AdminGUReportController::class, 'exportPdf'])->name('export-pdf');
                });

            Route::middleware('role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->prefix('stok-opname')
                ->name('stok-opname.')
                ->group(function (): void {
                    Route::get('/', [AdminGUStokOpnameController::class, 'index'])
                        ->middleware(LogReadActivity::class)
                        ->name('index');
                    Route::get('/draf', [AdminGUStokOpnameController::class, 'drafts'])
                        ->middleware(LogReadActivity::class)
                        ->name('drafts');
                    Route::post('/draf', [AdminGUStokOpnameController::class, 'saveDraft'])->name('draft');
                    Route::post('/reset', [AdminGUStokOpnameController::class, 'reset'])->name('reset');
                    Route::post('/terapkan', [AdminGUStokOpnameController::class, 'apply'])->name('apply');
                    Route::get('/berita-acara.pdf', [AdminGUStokOpnameController::class, 'pdf'])->name('pdf');
                });

            Route::middleware('permission:permintaan_barang.read|permintaan_barang.create')->group(function () {
                Route::get('/permintaan', [AdminGUPermintaanBarangController::class, 'index'])
                    ->name('permintaan.index');
                Route::get('/permintaan/barang-search', [AdminGUPermintaanBarangController::class, 'cariBarang'])
                    ->name('permintaan.barang-search');
                Route::post('/permintaan', [AdminGUPermintaanBarangController::class, 'store'])
                    ->middleware('permission:permintaan_barang.create')
                    ->name('permintaan.store');
            });

            // Admin GU reconciles receipts after Recording GU records physical receipt.
            Route::get('/pemindahan', [AdminGUPemindahanController::class, 'index'])
                ->middleware('role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->name('pemindahan.index');

            Route::middleware('role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->prefix('rekonsiliasi-penerimaan')
                ->name('rekonsiliasi-penerimaan.')
                ->group(function (): void {
                    Route::get('/', [AdminGURekonsiliasiPenerimaanController::class, 'index'])
                        ->name('index');
                    Route::post('/{penerimaan}/verifikasi', [AdminGURekonsiliasiPenerimaanController::class, 'verify'])
                        ->whereNumber('penerimaan')
                        ->name('verify');
                    Route::post('/{penerimaan}/tindak-lanjut', [AdminGURekonsiliasiPenerimaanController::class, 'recordFollowUp'])
                        ->whereNumber('penerimaan')
                        ->name('follow-up');
                });

            Route::middleware('role:Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->prefix('rekonsiliasi-pengeluaran')
                ->name('rekonsiliasi-pengeluaran.')
                ->group(function (): void {
                    Route::get('/', [AdminGURekonsiliasiPengeluaranController::class, 'index'])
                        ->name('index');
                    Route::post('/{pengeluaran}/draft', [AdminGURekonsiliasiPengeluaranController::class, 'saveDraft'])
                        ->whereNumber('pengeluaran')
                        ->name('draft');
                    Route::post('/{pengeluaran}/verifikasi', [AdminGURekonsiliasiPengeluaranController::class, 'verify'])
                        ->whereNumber('pengeluaran')
                        ->name('verify');
                });
        });

        // 3.2. SUB-ROLE: RECORDING GUDANG UMUM (Pencatatan Lapangan & Operasional)
        Route::prefix('recording-gu')->name('recording-gu.')->group(function () {
            Route::get('/dashboard', RecordingGUDashboardController::class)
                ->middleware(['role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin', LogReadActivity::class])
                ->name('dashboard');

            // Physical receipt is performed by Recording GU.
            Route::middleware('role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin')
                ->prefix('penerimaan')
                ->name('penerimaan.')
                ->group(function (): void {
                    Route::get('/', [RecordingGUPenerimaanController::class, 'index'])->name('index');
                    Route::post('/', [RecordingGUPenerimaanController::class, 'store'])->name('store');
                    Route::post('/konfirmasi-transfer', [RecordingGUPenerimaanController::class, 'confirmTransfer'])->name('confirm-transfer');
                });

            // Pengeluaran Operasional Unit & BPB
            Route::get('/pengeluaran', [RecordingGUPengeluaranController::class, 'index'])->name('pengeluaran.index');
            Route::get('/pengeluaran/items', [RecordingGUPengeluaranController::class, 'items'])->name('pengeluaran.items');
            Route::get('/pengeluaran/requesters', [RecordingGUPengeluaranController::class, 'requesters'])->name('pengeluaran.requesters');
            Route::get('/pengeluaran/borrowed-items', [RecordingGUPengeluaranController::class, 'borrowedItems'])->name('pengeluaran.borrowed-items');
            Route::get('/pengeluaran/return-options', [RecordingGUPengeluaranController::class, 'returnOptions'])->name('pengeluaran.return-options');
            Route::post('/pengeluaran', [RecordingGUPengeluaranController::class, 'store'])
                ->middleware('role:Recording Gudang Umum|recording_gudang_umum|Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->name('pengeluaran.store');
            Route::post('/pengeluaran/requester', [RecordingGUPengeluaranController::class, 'storeRequester'])
                ->middleware('role:Recording Gudang Umum|recording_gudang_umum|Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->name('pengeluaran.requester.store');
            Route::post('/pengeluaran/pengembalian', [RecordingGUPengeluaranController::class, 'storePengembalian'])
                ->middleware('role:Recording Gudang Umum|recording_gudang_umum|Admin Gudang Umum|admin_gudang_umum|Super Admin|super_admin')
                ->name('pengeluaran.pengembalian.store');
        });

        // Compatibility routes for Recording GU transfer receipt.
        Route::middleware([
            'role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin',
            'permission:pemindahan.terima',
        ])->group(function () {
            Route::get('/pemindahan', [AdminGUPemindahanController::class, 'index'])->name('pemindahan.index');
            Route::post('/pemindahan/{transfer}/konfirmasi', [AdminGUPemindahanController::class, 'konfirmasi'])->name('pemindahan.konfirmasi');
        });
        Route::middleware('role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin')
            ->prefix('penerimaan')
            ->name('penerimaan.')
            ->group(function (): void {
                Route::get('/', [RecordingGUPenerimaanController::class, 'index'])->name('index');
                Route::post('/', [RecordingGUPenerimaanController::class, 'store'])->name('store');
                Route::post('/konfirmasi-transfer', [RecordingGUPenerimaanController::class, 'confirmTransfer'])->name('confirm-transfer');
            });
        Route::get('/pengeluaran', [RecordingGUPengeluaranController::class, 'index'])->name('pengeluaran.index');
        Route::post('/pengeluaran', [RecordingGUPengeluaranController::class, 'store'])->name('pengeluaran.store');
        Route::get('/stok', [UnitBisnisStokController::class, 'index'])->name('stok.index');
        Route::get('/stok/export', [UnitBisnisStokController::class, 'downloadExport'])->name('stok.export');
        Route::get('/stok/{barang}/edit', [UnitBisnisStokController::class, 'edit'])->name('stok.edit');
        Route::put('/stok/{barang}', [UnitBisnisStokController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{barang}', [UnitBisnisStokController::class, 'destroy'])->name('stok.destroy');
        Route::get('/stok/{barang}/kartu', [UnitBisnisStokController::class, 'kartuStok'])->name('stok.kartu');
        Route::get('/stok/import-template', [UnitBisnisStokController::class, 'downloadImportTemplate'])->name('stok.import-template');
        Route::post('/stok/import-analyze', [UnitBisnisStokController::class, 'analyzeImport'])->name('stok.import-analyze');
        Route::post('/stok/import-process', [UnitBisnisStokController::class, 'processImport'])->name('stok.import-process');
    });

    // =========================================================================
    // COMPATIBILITY LAYER (Legacy Routes for Tests & Bookmarks)
    // =========================================================================
    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::middleware([
            'role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin',
            'permission:pemindahan.terima',
        ])->group(function () {
            Route::get('/pemindahan-unit', [AdminGUPemindahanController::class, 'index'])->name('pemindahan-unit.index');
            Route::post('/pemindahan-unit/{transfer}/konfirmasi', [AdminGUPemindahanController::class, 'konfirmasi'])->name('pemindahan-unit.konfirmasi');
        });
        Route::middleware('role:Recording Gudang Umum|recording_gudang_umum|Super Admin|super_admin')
            ->prefix('penerimaan')
            ->name('penerimaan.')
            ->group(function (): void {
                Route::get('/', [RecordingGUPenerimaanController::class, 'index'])->name('index');
                Route::post('/', [RecordingGUPenerimaanController::class, 'store'])->name('store');
            });
        Route::get('/pengeluaran', [RecordingGUPengeluaranController::class, 'index'])->name('pengeluaran.index');
        Route::post('/pengeluaran', [RecordingGUPengeluaranController::class, 'store'])->name('pengeluaran.store');
    });

    Route::prefix('master')->name('master.')->group(function () {
        Route::middleware('permission:barang.import')->prefix('barang/import')->name('barang.import.')->group(function () {
            Route::get('/', [MasterBarangController::class, 'importIndex'])->name('index');
            Route::get('/template', [MasterBarangController::class, 'downloadImportTemplate'])->name('template');
            Route::post('/excel', [MasterBarangController::class, 'parseImportExcel'])->name('excel');
            Route::post('/analyze', [MasterBarangController::class, 'analyzeImport'])->name('analyze');
            Route::post('/confirm', [MasterBarangController::class, 'confirmImport'])->name('confirm');
        });

        Route::get('/barang', [MasterBarangController::class, 'index'])->name('barang.index');
        Route::middleware('permission:barang.manage')->group(function () {
            Route::post('/barang', [MasterBarangController::class, 'store'])->name('barang.store');
            Route::put('/barang/{masterBarang}', [MasterBarangController::class, 'update'])->name('barang.update');
            Route::post('/barang/{masterBarang}/gudang', [MasterBarangController::class, 'register'])->name('barang.register');
            Route::patch('/barang/{masterBarang}/toggle', [MasterBarangController::class, 'toggle'])->name('barang.toggle');
            Route::delete('/barang/{masterBarang}', [MasterBarangController::class, 'destroy'])->name('barang.destroy');
        });

        Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
        Route::post('/kategori', [KategoriController::class, 'store'])
            ->middleware('permission:kategori.create')
            ->name('kategori.store');
        Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])
            ->middleware('permission:kategori.manage')
            ->name('kategori.update');
        Route::patch('/kategori/{kategori}/toggle', [KategoriController::class, 'toggle'])
            ->middleware('permission:kategori.manage')
            ->name('kategori.toggle');

        Route::get('/satuan', [SatuanController::class, 'index'])->name('satuan.index');
        Route::post('/satuan', [SatuanController::class, 'store'])
            ->middleware('permission:satuan.create')
            ->name('satuan.store');
        Route::put('/satuan/{satuan}', [SatuanController::class, 'update'])
            ->middleware('permission:satuan.manage')
            ->name('satuan.update');
        Route::patch('/satuan/{satuan}/toggle', [SatuanController::class, 'toggle'])
            ->middleware('permission:satuan.manage')
            ->name('satuan.toggle');
        Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy'])
            ->middleware('permission:satuan.manage')
            ->name('satuan.destroy');

        Route::post('/area', [DivisiPemintaController::class, 'store'])
            ->middleware('permission:area.create')
            ->name('area.store');
        Route::put('/area/{area}', [DivisiPemintaController::class, 'update'])
            ->middleware('permission:area.manage')
            ->name('area.update');
        Route::patch('/area/{area}/toggle', [DivisiPemintaController::class, 'toggle'])
            ->middleware('permission:area.manage')
            ->name('area.toggle');
        Route::delete('/area/{area}', [DivisiPemintaController::class, 'destroy'])
            ->middleware('permission:area.manage')
            ->name('area.destroy');

        Route::get('/barang-store', fn () => redirect()->route('master.barang.index'))->name('barang-store.index');
    });

    // API Quick-Add Inline Services (Reusable Component)
    Route::prefix('api/quick-add')->group(function () {
        Route::get('/check-similarity', [QuickAddController::class, 'checkSimilarity']);
        Route::middleware('permission:barang.create')->post('/barang', [QuickAddController::class, 'storeBarang']);
        Route::middleware('permission:kategori.create')->post('/kategori', [QuickAddController::class, 'storeKategori']);
        Route::middleware('permission:satuan.create')->post('/satuan', [QuickAddController::class, 'storeSatuan']);
    });

    // Keep the paths used by existing Quick-Add forms working alongside the API paths.
    Route::prefix('quick-add')->group(function () {
        Route::get('/check-similarity', [QuickAddController::class, 'checkSimilarity']);
        Route::middleware('permission:barang.create')->post('/barang', [QuickAddController::class, 'storeBarang']);
        Route::middleware('permission:kategori.create')->post('/kategori', [QuickAddController::class, 'storeKategori']);
        Route::middleware('permission:satuan.create')->post('/satuan', [QuickAddController::class, 'storeSatuan']);
    });
});
