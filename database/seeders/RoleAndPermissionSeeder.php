<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            // POS
            ['name' => 'Access POS Counter', 'slug' => 'pos.counter', 'module' => 'pos', 'description' => 'Create and process customer sales.'],
            ['name' => 'Apply POS Discounts', 'slug' => 'pos.discount', 'module' => 'pos', 'description' => 'Apply custom discounts to sales.'],
            ['name' => 'Hold POS Bills', 'slug' => 'pos.hold', 'module' => 'pos', 'description' => 'Temporarily park and resume sale tickets.'],

            // Inventory & Products
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory', 'description' => 'View stock quantities, lot batches and valuation.'],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'module' => 'inventory', 'description' => 'Adjust stock, record damage, and initiate transfers.'],
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products', 'description' => 'View products catalog, pricing and barcodes.'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'module' => 'products', 'description' => 'Create, edit, and archive products and price lists.'],
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'module' => 'products', 'description' => 'Create and organize product and expense categories.'],
            ['name' => 'Manage Units', 'slug' => 'units.manage', 'module' => 'products', 'description' => 'Configure measurement units and conversion multipliers.'],

            // Purchases & Suppliers
            ['name' => 'View Purchases', 'slug' => 'purchases.view', 'module' => 'purchases', 'description' => 'View supplier purchases and bills.'],
            ['name' => 'Create Purchases', 'slug' => 'purchases.create', 'module' => 'purchases', 'description' => 'Create new purchase invoices and receive lots.'],
            ['name' => 'View Suppliers', 'slug' => 'suppliers.view', 'module' => 'suppliers', 'description' => 'View Mandi commission agents and farmers directory.'],
            ['name' => 'Manage Suppliers', 'slug' => 'suppliers.manage', 'module' => 'suppliers', 'description' => 'Create, edit, and manage supplier profiles and ledger balances.'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'module' => 'customers', 'description' => 'View retail and commercial customer accounts.'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'customers', 'description' => 'Create and edit customers, credit limits and balances.'],

            // Expenses
            ['name' => 'Manage Expenses', 'slug' => 'expenses.manage', 'module' => 'expenses', 'description' => 'Log petty cash and daily shop overheads.'],

            // Accounting
            ['name' => 'View Accounting Ledgers', 'slug' => 'accounting.view', 'module' => 'accounting', 'description' => 'Inspect general ledger and trial balance.'],
            ['name' => 'Manage Accounting', 'slug' => 'accounting.manage', 'module' => 'accounting', 'description' => 'Post journal entries and manage accounts.'],

            // Reports
            ['name' => 'View Sales Reports', 'slug' => 'reports.sales', 'module' => 'reports', 'description' => 'Generate daily, weekly and monthly sales analysis.'],
            ['name' => 'View Financial Reports', 'slug' => 'reports.financial', 'module' => 'reports', 'description' => 'Access profit & loss statements and balance sheets.'],

            // Users
            ['name' => 'Manage Staff & Users', 'slug' => 'users.manage', 'module' => 'users', 'description' => 'Create, edit, suspend employees and assign branches.'],

            // Settings
            ['name' => 'Manage Shop Settings', 'slug' => 'settings.manage', 'module' => 'settings', 'description' => 'Configure invoice print templates, units and preferences.'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Create Global System Roles (tenant_id = null)
        $roles = [
            [
                'name' => 'Business Owner',
                'slug' => 'owner',
                'description' => 'Full administrative access across all tenant operations and branches.',
                'is_system' => true,
                'permissions' => Permission::all()->pluck('slug')->toArray(),
            ],
            [
                'name' => 'General Manager',
                'slug' => 'manager',
                'description' => 'Oversees day-to-day operations, purchases, inventory, sales, and employee rosters.',
                'is_system' => true,
                'permissions' => [
                    'pos.counter', 'pos.discount', 'pos.hold',
                    'inventory.view', 'inventory.manage',
                    'products.view', 'products.manage', 'categories.manage', 'units.manage',
                    'purchases.view', 'purchases.create',
                    'suppliers.view', 'suppliers.manage',
                    'customers.view', 'customers.manage',
                    'expenses.manage', 'reports.sales',
                    'users.manage', 'settings.manage',
                ],
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Financial operations, journals, cash/bank accounts, expenses, and fiscal reporting.',
                'is_system' => true,
                'permissions' => [
                    'accounting.view', 'accounting.manage',
                    'expenses.manage', 'purchases.view',
                    'suppliers.view', 'suppliers.manage',
                    'customers.view', 'customers.manage',
                    'reports.sales', 'reports.financial',
                ],
            ],
            [
                'name' => 'Purchase Operator',
                'slug' => 'purchase_operator',
                'description' => 'Mandi auctions procurement, vendor invoices, weighing, and batch receiving.',
                'is_system' => true,
                'permissions' => [
                    'purchases.view', 'purchases.create',
                    'suppliers.view', 'suppliers.manage',
                    'inventory.view',
                ],
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Counter sales billing, barcode scanning, cash collection, and thermal receipts.',
                'is_system' => true,
                'permissions' => [
                    'pos.counter', 'pos.hold',
                    'customers.view', 'customers.manage',
                ],
            ],
            [
                'name' => 'Inventory Manager',
                'slug' => 'inventory_manager',
                'description' => 'Warehouse stock, spoilage/wastage recording, cold room lot transfers.',
                'is_system' => true,
                'permissions' => [
                    'inventory.view', 'inventory.manage',
                    'products.view', 'products.manage', 'categories.manage', 'units.manage',
                    'purchases.view',
                ],
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only visibility for auditing, monitoring, and overview reporting.',
                'is_system' => true,
                'permissions' => [
                    'inventory.view', 'purchases.view',
                    'products.view', 'suppliers.view', 'customers.view',
                    'reports.sales',
                ],
            ],
        ];

        foreach ($roles as $r) {
            $perms = $r['permissions'];
            unset($r['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $r['slug'], 'tenant_id' => null],
                $r
            );

            $permissionIds = Permission::whereIn('slug', $perms)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
