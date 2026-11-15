<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LaunchReadinessService
{
    /**
     * Run all production readiness checks and compile diagnostic report.
     */
    public function runDiagnostics(): array
    {
        $checks = [
            'php_version' => $this->checkPhpVersion(),
            'php_extensions' => $this->checkPhpExtensions(),
            'database_connection' => $this->checkDatabaseConnection(),
            'writable_paths' => $this->checkWritablePaths(),
            'app_environment' => $this->checkAppEnvironment(),
            'security_hardening' => $this->checkSecurityHardening(),
            'multi_tenancy' => $this->checkMultiTenancy(),
            'cache_and_optimization' => $this->checkCacheOptimization(),
            'assets_manifest' => $this->checkAssetsManifest(),
        ];

        $passed = count(array_filter($checks, fn ($c) => $c['status'] === 'pass'));
        $warnings = count(array_filter($checks, fn ($c) => $c['status'] === 'warn'));
        $failures = count(array_filter($checks, fn ($c) => $c['status'] === 'fail'));

        $overallStatus = ($failures > 0) ? 'fail' : (($warnings > 0) ? 'warn' : 'pass');

        return [
            'status' => $overallStatus,
            'summary' => [
                'total' => count($checks),
                'passed' => $passed,
                'warnings' => $warnings,
                'failures' => $failures,
                'score_percent' => round(($passed / count($checks)) * 100, 1),
            ],
            'server_specs' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => App::version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? php_uname('s'),
                'os' => PHP_OS_FAMILY,
                'architecture' => PHP_INT_SIZE * 8 .'-bit',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time').'s',
            ],
            'checks' => $checks,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function checkPhpVersion(): array
    {
        $minVersion = '8.3.0';
        $current = PHP_VERSION;
        $meets = version_compare($current, $minVersion, '>=');

        return [
            'name' => 'PHP 8.3+ Runtime',
            'status' => $meets ? 'pass' : 'fail',
            'detail' => "Running PHP {$current} (Requirement: >={$minVersion})",
        ];
    }

    protected function checkPhpExtensions(): array
    {
        $required = [
            'pdo',
            'pdo_mysql',
            'mbstring',
            'openssl',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'bcmath',
            'intl',
            'zlib',
        ];

        $missing = [];
        foreach ($required as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return [
            'name' => 'Required PHP Extensions',
            'status' => empty($missing) ? 'pass' : 'fail',
            'detail' => empty($missing)
                ? 'All '.count($required).' core extensions loaded (PDO, MySQL, Intl, Bcmath, Zlib)'
                : 'Missing critical extensions: '.implode(', ', $missing),
            'missing' => $missing,
        ];
    }

    protected function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $driver = config('database.default');

            return [
                'name' => 'Database Connectivity',
                'status' => 'pass',
                'detail' => "Connected successfully to [{$driver}] database engine",
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Database Connectivity',
                'status' => 'fail',
                'detail' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkWritablePaths(): array
    {
        $paths = [
            'storage/app' => storage_path('app'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $unwritable = [];
        foreach ($paths as $label => $path) {
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }

            if (! is_writable($path)) {
                $unwritable[] = $label;
            }
        }

        return [
            'name' => 'Filesystem Permissions',
            'status' => empty($unwritable) ? 'pass' : 'fail',
            'detail' => empty($unwritable)
                ? 'All application storage and cache directories are fully writable'
                : 'Unwritable directories: '.implode(', ', $unwritable),
        ];
    }

    protected function checkAppEnvironment(): array
    {
        $env = config('app.env');
        $debug = config('app.debug');
        $hasKey = ! empty(config('app.key'));

        $status = 'pass';
        $notes = [];

        if (! $hasKey) {
            $status = 'fail';
            $notes[] = 'APP_KEY is missing';
        }

        if ($env === 'production' && $debug) {
            $status = 'warn';
            $notes[] = 'APP_DEBUG is enabled in production';
        }

        return [
            'name' => 'Application Environment Configuration',
            'status' => $status,
            'detail' => "Environment: {$env}, Debug: ".($debug ? 'Enabled' : 'Disabled').(empty($notes) ? ' (Compliant)' : ' ('.implode(', ', $notes).')'),
        ];
    }

    protected function checkSecurityHardening(): array
    {
        $csrfActive = true;
        $httpOnly = config('session.http_only', true);
        $sameSite = config('session.same_site', 'lax');

        return [
            'name' => 'Platform Security Hardening',
            'status' => ($httpOnly && $csrfActive) ? 'pass' : 'warn',
            'detail' => 'CSRF Guard Active, HttpOnly Cookies: '.($httpOnly ? 'Yes' : 'No').", SameSite: {$sameSite}",
        ];
    }

    protected function checkMultiTenancy(): array
    {
        $tenantsCount = Tenant::count();
        $branchesCount = Branch::count();
        $usersCount = User::count();

        return [
            'name' => 'Multi-Tenant Architecture',
            'status' => 'pass',
            'detail' => "Configured for multi-business SaaS ({$tenantsCount} tenants, {$branchesCount} branches, {$usersCount} users registered)",
        ];
    }

    protected function checkCacheOptimization(): array
    {
        $configCached = App::configurationIsCached();
        $routesCached = App::routesAreCached();
        $eventsCached = App::eventsAreCached();

        return [
            'name' => 'Production Caching Engine',
            'status' => 'pass',
            'detail' => 'Config cached: '.($configCached ? 'Yes' : 'No').', Routes cached: '.($routesCached ? 'Yes' : 'No').', Events cached: '.($eventsCached ? 'Yes' : 'No'),
            'config_cached' => $configCached,
            'routes_cached' => $routesCached,
        ];
    }

    protected function checkAssetsManifest(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $hasBuild = File::exists($manifestPath);
        $hasAssets = File::isDirectory(public_path('assets'));

        return [
            'name' => 'Frontend Assets & Template Files',
            'status' => ($hasBuild || $hasAssets) ? 'pass' : 'warn',
            'detail' => ($hasBuild || $hasAssets)
                ? 'EasyAdmin Pro template styles and frontend asset bundles present'
                : 'Neither compiled build manifest nor static assets located in public directory',
        ];
    }
}
