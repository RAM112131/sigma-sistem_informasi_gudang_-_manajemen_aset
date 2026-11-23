<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\LandingModuleCatalog;
use Illuminate\Contracts\View\View;

final class WelcomeController extends Controller
{
    public function __construct(
        private readonly LandingModuleCatalog $landingModuleCatalog,
    ) {}

    /**
     * Tampilkan halaman welcome/landing publik SIGMA (Sistem Informasi Gudang & Manajemen Aset).
     */
    public function __invoke(): View
    {
        $appName = (string) config('app.name', 'SIGMA');
        $version = (string) config('app.version', app()->version());

        $modules = $this->landingModuleCatalog->activeModules();
        $activeModuleCount = count($modules);

        return view('welcome', compact('appName', 'version', 'modules', 'activeModuleCount'));
    }
}
