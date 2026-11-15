<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WastageRecord;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');

        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Generate a complete platform database backup.
     */
    public function createBackup(?string $connection = null, bool $compress = true): array
    {
        $conn = $connection ?? config('database.default');
        $timestamp = now()->format('Y-m-d_His');
        $ext = $compress ? 'sql.gz' : 'sql';
        $filename = "veggitable_backup_{$timestamp}.{$ext}";
        $filePath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        $sql = $this->generateSqlDump($conn);
        $tablesCount = $this->getTablesCount($conn);

        if ($compress) {
            $content = gzencode($sql, 9);
            File::put($filePath, $content);
        } else {
            File::put($filePath, $sql);
        }

        $sizeBytes = File::size($filePath);
        $sha256 = hash_file('sha256', $filePath);

        $metadata = [
            'filename' => $filename,
            'path' => $filePath,
            'size_bytes' => $sizeBytes,
            'formatted_size' => $this->formatBytes($sizeBytes),
            'sha256' => $sha256,
            'created_at' => now()->toIso8601String(),
            'type' => 'full',
            'compressed' => $compress,
            'tables_count' => $tablesCount,
            'connection' => $conn,
        ];

        $this->recordInManifest($metadata);

        Log::info("Platform database backup created: [{$filename}] ({$metadata['formatted_size']}), SHA256: {$sha256}");

        return $metadata;
    }

    /**
     * Export all isolated tenant data across business modules into structured JSON.
     */
    public function exportTenantData(Tenant $tenant, bool $compress = false): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $ext = $compress ? 'json.gz' : 'json';
        $filename = "tenant_{$tenant->slug}_{$timestamp}.{$ext}";
        $filePath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        $tenantId = $tenant->id;

        $branches = Branch::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->select(['id', 'tenant_id', 'current_branch_id', 'name', 'email', 'phone', 'is_tenant_owner', 'status', 'created_at'])->get();
        $categories = Category::where('tenant_id', $tenantId)->get();
        $units = Unit::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();
        $customers = Customer::where('tenant_id', $tenantId)->get();
        $suppliers = Supplier::where('tenant_id', $tenantId)->get();
        $purchases = PurchaseInvoice::where('tenant_id', $tenantId)->with('items')->get();
        $sales = SalesInvoice::where('tenant_id', $tenantId)->with(['items', 'payments'])->get();
        $inventory = InventoryStock::where('tenant_id', $tenantId)->get();
        $movements = StockMovement::where('tenant_id', $tenantId)->get();
        $transfers = StockTransfer::where('tenant_id', $tenantId)->with('items')->get();
        $adjustments = StockAdjustment::where('tenant_id', $tenantId)->with('items')->get();
        $wastages = WastageRecord::where('tenant_id', $tenantId)->with('items')->get();
        $expenses = Expense::where('tenant_id', $tenantId)->get();
        $accounts = FinancialAccount::where('tenant_id', $tenantId)->get();
        $coa = ChartOfAccount::where('tenant_id', $tenantId)->get();
        $journals = JournalEntry::where('tenant_id', $tenantId)->with('lines')->get();
        $notifications = DB::table('notifications')->where('tenant_id', $tenantId)->get();

        $payload = [
            'version' => '1.0',
            'system' => 'Veggitable Vegetable Shop SaaS Management System',
            'exported_at' => now()->toIso8601String(),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
                'timezone' => $tenant->timezone,
                'status' => $tenant->status,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ],
            'summary_counts' => [
                'branches' => $branches->count(),
                'users' => $users->count(),
                'categories' => $categories->count(),
                'units' => $units->count(),
                'products' => $products->count(),
                'customers' => $customers->count(),
                'suppliers' => $suppliers->count(),
                'purchase_invoices' => $purchases->count(),
                'sales_invoices' => $sales->count(),
                'inventory_items' => $inventory->count(),
                'stock_movements' => $movements->count(),
                'stock_transfers' => $transfers->count(),
                'stock_adjustments' => $adjustments->count(),
                'wastage_records' => $wastages->count(),
                'expenses' => $expenses->count(),
                'financial_accounts' => $accounts->count(),
                'chart_of_accounts' => $coa->count(),
                'journal_entries' => $journals->count(),
                'notifications' => $notifications->count(),
            ],
            'data' => [
                'branches' => $branches->toArray(),
                'users' => $users->toArray(),
                'categories' => $categories->toArray(),
                'units' => $units->toArray(),
                'products' => $products->toArray(),
                'customers' => $customers->toArray(),
                'suppliers' => $suppliers->toArray(),
                'purchases' => $purchases->toArray(),
                'sales' => $sales->toArray(),
                'inventory' => $inventory->toArray(),
                'stock_movements' => $movements->toArray(),
                'stock_transfers' => $transfers->toArray(),
                'stock_adjustments' => $adjustments->toArray(),
                'wastage_records' => $wastages->toArray(),
                'expenses' => $expenses->toArray(),
                'financial_accounts' => $accounts->toArray(),
                'chart_of_accounts' => $coa->toArray(),
                'journal_entries' => $journals->toArray(),
                'notifications' => $notifications->toArray(),
            ],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($compress) {
            $content = gzencode($json, 9);
            File::put($filePath, $content);
        } else {
            File::put($filePath, $json);
        }

        $sizeBytes = File::size($filePath);
        $sha256 = hash_file('sha256', $filePath);

        $metadata = [
            'filename' => $filename,
            'path' => $filePath,
            'size_bytes' => $sizeBytes,
            'formatted_size' => $this->formatBytes($sizeBytes),
            'sha256' => $sha256,
            'created_at' => now()->toIso8601String(),
            'type' => 'tenant_export',
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'compressed' => $compress,
        ];

        $this->recordInManifest($metadata);

        Log::info("Tenant data exported for [{$tenant->name}]: [{$filename}] ({$metadata['formatted_size']})");

        return $metadata;
    }

    /**
     * List all available backups and tenant exports.
     */
    public function listBackups(): array
    {
        $manifest = $this->readManifest();
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $name = $file->getFilename();
            if ($name === 'manifest.json') {
                continue;
            }

            if (isset($manifest[$name])) {
                $backups[] = $manifest[$name];
            } else {
                $sizeBytes = $file->getSize();
                $isTenant = str_starts_with($name, 'tenant_');
                $backups[] = [
                    'filename' => $name,
                    'path' => $file->getPathname(),
                    'size_bytes' => $sizeBytes,
                    'formatted_size' => $this->formatBytes($sizeBytes),
                    'sha256' => hash_file('sha256', $file->getPathname()),
                    'created_at' => date('c', $file->getMTime()),
                    'type' => $isTenant ? 'tenant_export' : 'full',
                    'compressed' => str_ends_with($name, '.gz'),
                ];
            }
        }

        // Sort newest first
        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Purge backups older than given number of days.
     */
    public function cleanOldBackups(int $keepDays = 30): int
    {
        $cutoff = now()->subDays($keepDays)->timestamp;
        $files = File::files($this->backupDir);
        $purgedCount = 0;
        $manifest = $this->readManifest();

        foreach ($files as $file) {
            $name = $file->getFilename();
            if ($name === 'manifest.json') {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                unset($manifest[$name]);
                $purgedCount++;
                Log::info("Purged expired backup: [{$name}]");
            }
        }

        $this->writeManifest($manifest);

        return $purgedCount;
    }

    /**
     * Delete a single backup file securely.
     */
    public function deleteBackup(string $filename): bool
    {
        $safeName = basename($filename);
        $path = $this->backupDir.DIRECTORY_SEPARATOR.$safeName;

        if (File::exists($path)) {
            File::delete($path);
            $manifest = $this->readManifest();
            unset($manifest[$safeName]);
            $this->writeManifest($manifest);
            Log::info("Deleted backup file: [{$safeName}]");

            return true;
        }

        return false;
    }

    /**
     * Get validated path to backup file for download.
     */
    public function getBackupPath(string $filename): ?string
    {
        $safeName = basename($filename);
        $path = $this->backupDir.DIRECTORY_SEPARATOR.$safeName;

        return File::exists($path) ? $path : null;
    }

    /**
     * Generate SQL dump script.
     */
    protected function generateSqlDump(string $connection): string
    {
        $driver = config("database.connections.{$connection}.driver", 'mysql');

        $out = "-- Veggitable Multi-Tenant SaaS Database Backup\n";
        $out .= '-- Generated: '.now()->toIso8601String()."\n";
        $out .= "-- Connection: {$connection} (Driver: {$driver})\n";
        $out .= "-- --------------------------------------------------------\n\n";

        if ($driver === 'mysql') {
            $out .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $out .= "SET NAMES utf8mb4;\n\n";

            $tables = DB::connection($connection)->select('SHOW FULL TABLES WHERE Table_Type = "BASE TABLE"');

            foreach ($tables as $tableObj) {
                $tblArr = (array) $tableObj;
                $tableName = reset($tblArr);

                $createTbl = DB::connection($connection)->select("SHOW CREATE TABLE `{$tableName}`");
                $createArr = (array) $createTbl[0];
                $createSql = $createArr['Create Table'] ?? '';

                $out .= "-- Table structure for `{$tableName}`\n";
                $out .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $out .= $createSql.";\n\n";

                // Table rows
                $rows = DB::connection($connection)->table($tableName)->get();
                if ($rows->isNotEmpty()) {
                    $out .= "-- Dumping data for `{$tableName}`\n";
                    foreach ($rows as $row) {
                        $rowArr = (array) $row;
                        $keys = array_map(fn ($k) => "`{$k}`", array_keys($rowArr));
                        $vals = array_map(function ($v) use ($connection) {
                            if ($v === null) {
                                return 'NULL';
                            }

                            return DB::connection($connection)->getPdo()->quote((string) $v);
                        }, array_values($rowArr));

                        $out .= "INSERT INTO `{$tableName}` (".implode(', ', $keys).') VALUES ('.implode(', ', $vals).");\n";
                    }
                    $out .= "\n";
                }
            }

            $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        } elseif ($driver === 'sqlite') {
            $tables = DB::connection($connection)->select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            foreach ($tables as $t) {
                $tableName = $t->name;
                $createSql = $t->sql;

                $out .= "-- Table structure for `{$tableName}`\n";
                $out .= "DROP TABLE IF EXISTS \"{$tableName}\";\n";
                $out .= $createSql.";\n\n";

                $rows = DB::connection($connection)->table($tableName)->get();
                if ($rows->isNotEmpty()) {
                    foreach ($rows as $row) {
                        $rowArr = (array) $row;
                        $keys = array_map(fn ($k) => "\"{$k}\"", array_keys($rowArr));
                        $vals = array_map(function ($v) use ($connection) {
                            if ($v === null) {
                                return 'NULL';
                            }

                            return DB::connection($connection)->getPdo()->quote((string) $v);
                        }, array_values($rowArr));

                        $out .= "INSERT INTO \"{$tableName}\" (".implode(', ', $keys).') VALUES ('.implode(', ', $vals).");\n";
                    }
                    $out .= "\n";
                }
            }
        }

        return $out;
    }

    /**
     * Count total tables in database.
     */
    protected function getTablesCount(string $connection): int
    {
        try {
            $driver = config("database.connections.{$connection}.driver", 'mysql');
            if ($driver === 'mysql') {
                return count(DB::connection($connection)->select('SHOW TABLES'));
            } elseif ($driver === 'sqlite') {
                return count(DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"));
            }
        } catch (Exception $e) {
            // Ignore
        }

        return 0;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    protected function readManifest(): array
    {
        $manifestPath = $this->backupDir.DIRECTORY_SEPARATOR.'manifest.json';
        if (File::exists($manifestPath)) {
            $json = File::get($manifestPath);

            return json_decode($json, true) ?: [];
        }

        return [];
    }

    protected function writeManifest(array $manifest): void
    {
        $manifestPath = $this->backupDir.DIRECTORY_SEPARATOR.'manifest.json';
        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function recordInManifest(array $entry): void
    {
        $manifest = $this->readManifest();
        $manifest[$entry['filename']] = $entry;
        $this->writeManifest($manifest);
    }
}
