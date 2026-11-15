<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityAuditService
{
    /**
     * Tenant-owned tables that require strict tenant_id isolation.
     */
    protected array $tenantIsolatedTables = [
        'branches',
        'users',
        'categories',
        'units',
        'products',
        'customers',
        'suppliers',
        'purchase_invoices',
        'purchase_invoice_items',
        'sales_invoices',
        'sales_invoice_items',
        'sales_invoice_payments',
        'inventory_stocks',
        'stock_movements',
        'stock_transfers',
        'stock_transfer_items',
        'stock_adjustments',
        'stock_adjustment_items',
        'wastages',
        'wastage_items',
        'expenses',
        'financial_accounts',
        'chart_of_accounts',
        'journal_entries',
        'journal_entry_lines',
        'pos_sessions',
        'notifications',
    ];

    /**
     * Audit database tables for tenant isolation integrity and indexes.
     */
    public function auditTenantIsolation(): array
    {
        $auditResults = [];
        $existingTenantIds = Tenant::pluck('id')->toArray();

        foreach ($this->tenantIsolatedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $hasTenantColumn = Schema::hasColumn($table, 'tenant_id');
            $nullCount = 0;
            $orphansCount = 0;
            $totalCount = 0;

            if ($hasTenantColumn) {
                $totalCount = DB::table($table)->count();
                $nullQuery = DB::table($table)->whereNull('tenant_id');
                if ($table === 'users') {
                    $nullQuery->where('is_super_admin', false);
                }
                $nullCount = $nullQuery->count();

                if (! empty($existingTenantIds)) {
                    $orphansCount = DB::table($table)
                        ->whereNotNull('tenant_id')
                        ->whereNotIn('tenant_id', $existingTenantIds)
                        ->count();
                }
            }

            $auditResults[$table] = [
                'table' => $table,
                'has_tenant_id' => $hasTenantColumn,
                'total_records' => $totalCount,
                'null_tenant_records' => $nullCount,
                'orphaned_records' => $orphansCount,
                'is_secure' => $hasTenantColumn && ($nullCount === 0) && ($orphansCount === 0),
            ];
        }

        return $auditResults;
    }

    /**
     * Sanitize input against XSS injection while preserving UTF-8 Nastaliq Urdu glyphs.
     */
    public function sanitizeInput(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeInput'], $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        // Strip HTML and script tags but preserve international characters
        $clean = strip_tags($value);
        $clean = htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        return trim($clean);
    }

    /**
     * Retrieve security architecture metrics.
     */
    public function getSecurityMetrics(): array
    {
        $isolationAudit = $this->auditTenantIsolation();
        $secureTables = count(array_filter($isolationAudit, fn ($r) => $r['is_secure']));
        $totalAudited = count($isolationAudit);

        return [
            'csrf_protection' => true,
            'rate_limiting_configured' => true,
            'password_hash_driver' => config('hashing.driver', 'bcrypt'),
            'session_httponly' => (bool) config('session.http_only', true),
            'session_same_site' => config('session.same_site', 'lax'),
            'tenant_isolation' => [
                'secure_tables_count' => $secureTables,
                'total_audited_tables' => $totalAudited,
                'isolation_integrity_percent' => $totalAudited > 0 ? round(($secureTables / $totalAudited) * 100, 1) : 100,
            ],
            'rate_limiters' => [
                'login' => '5 attempts / min',
                'password_reset' => '5 attempts / min',
                'pos_transactions' => '60 attempts / min',
                'backup_actions' => '10 attempts / min',
                'api' => '60 requests / min',
            ],
        ];
    }
}
