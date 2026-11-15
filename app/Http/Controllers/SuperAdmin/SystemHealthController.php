<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\LaunchReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(
        protected LaunchReadinessService $readinessService
    ) {}

    /**
     * Display live system health, server specs, and production readiness dashboard.
     */
    public function index(): View
    {
        $diagnostics = $this->readinessService->runDiagnostics();

        return view('superadmin.system-health.index', compact('diagnostics'));
    }

    /**
     * Optimize application caches for high-traffic production environments.
     */
    public function optimize(): RedirectResponse
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return redirect()->route('superadmin.health.index')
                ->with('success', 'Production optimization complete! Configuration, routes, and Blade templates cached.');
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.health.index')
                ->with('error', 'Optimization failed: '.$e->getMessage());
        }
    }

    /**
     * Clear all cached files (configuration, routes, views, application cache).
     */
    public function clear(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');

            return redirect()->route('superadmin.health.index')
                ->with('success', 'Application caches successfully purged (optimize:clear executed).');
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.health.index')
                ->with('error', 'Clear failed: '.$e->getMessage());
        }
    }
}
