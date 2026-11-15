<!-- Sidebar -->
<aside class="sidebar">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <a href="{{ url('/') }}" class="sidebar-logo">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable">
      <span class="sidebar-logo-text">
        <span class="sidebar-logo-name">Veggitable</span>
        <span class="sidebar-logo-tagline">SaaS Platform</span>
      </span>
    </a>
    <button class="sidebar-close">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Sidebar Navigation -->
  <nav class="sidebar-nav">
    <ul class="nav-menu">
      <li class="nav-item">
        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}" data-tooltip="{{ __('common.dashboard') }}">
          <i class="ph-duotone ph-squares-four"></i>
          <span>{{ __('common.dashboard') }}</span>
        </a>
      </li>

      <!-- SaaS Central Platform Section -->
      <li class="nav-heading"><span>Platform Super Admin</span></li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" href="{{ route('superadmin.dashboard') }}" data-tooltip="Admin Dashboard">
          <i class="ph-duotone ph-gauge"></i>
          <span>Super Admin Dashboard</span>
        </a>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('superadmin.tenants.*') || request()->routeIs('tenant.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('superadmin.tenants.*') || request()->routeIs('tenant.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="Businesses">
          <i class="ph-duotone ph-buildings"></i>
          <span>Businesses & Tenants</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('superadmin.tenants.index') ? 'active' : '' }}" href="{{ route('superadmin.tenants.index') }}">Manage Businesses</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.register.create') ? 'active' : '' }}" href="{{ route('tenant.register.create') }}">Register New Business</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.records.index') ? 'active' : '' }}" href="{{ route('tenant.records.index') }}">Tenant Data & Records</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('superadmin.plans.*') || request()->routeIs('superadmin.subscriptions.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('superadmin.plans.*') || request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="Subscriptions">
          <i class="ph-duotone ph-credit-card"></i>
          <span>Subscriptions & Billing</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}" href="{{ route('superadmin.plans.index') }}">Subscription Plans</a></li>
          <li><a class="nav-link {{ request()->routeIs('superadmin.subscriptions.payments') || request()->routeIs('superadmin.subscriptions.show') ? 'active' : '' }}" href="{{ route('superadmin.subscriptions.payments') }}">Payments & Invoices</a></li>
          <li><a class="nav-link {{ request()->routeIs('superadmin.subscriptions.create') ? 'active' : '' }}" href="{{ route('superadmin.subscriptions.create') }}">Record Payment</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('superadmin.reports.*') ? 'active' : '' }}" href="{{ route('superadmin.reports.index') }}" data-tooltip="Platform Reports">
          <i class="ph-duotone ph-chart-line-up"></i>
          <span>Platform Reports</span>
        </a>
      </li>

      <!-- Tenant Operations Section -->
      <li class="nav-heading"><span>Shop Operations</span></li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}" href="{{ route('tenant.dashboard') }}" data-tooltip="{{ __('Executive Dashboard') }}">
          <i class="ph-duotone ph-gauge"></i>
          <span>{{ __('Executive Dashboard') }}</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('tenant.pos.*') ? 'active' : '' }}" href="{{ route('tenant.pos.counter') }}" data-tooltip="{{ __('POS Cashier') }}">
          <i class="ph-duotone ph-shopping-cart"></i>
          <span>{{ __('POS Cashier Counter') }}</span>
        </a>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.sales.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.sales.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Sales & Orders') }}">
          <i class="ph-duotone ph-receipt"></i>
          <span>{{ __('Sales & Invoices') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.sales.index') ? 'active' : '' }}" href="{{ route('tenant.sales.index') }}">{{ __('All Sales Invoices') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.pos.counter') ? 'active' : '' }}" href="{{ route('tenant.pos.counter') }}">{{ __('New POS Sale') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.products.*') || request()->routeIs('tenant.categories.*') || request()->routeIs('tenant.units.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.products.*') || request()->routeIs('tenant.categories.*') || request()->routeIs('tenant.units.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Vegetables & Fruits') }}">
          <i class="ph-duotone ph-plant"></i>
          <span>{{ __('Vegetables & Fruits') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}" href="{{ route('tenant.products.index') }}">{{ __('Product List') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}" href="{{ route('tenant.categories.index') }}">{{ __('Categories') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.units.*') ? 'active' : '' }}" href="{{ route('tenant.units.index') }}">{{ __('Units & Conversions') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.suppliers.*') || request()->routeIs('tenant.customers.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.suppliers.*') || request()->routeIs('tenant.customers.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Suppliers & Customers') }}">
          <i class="ph-duotone ph-users-three"></i>
          <span>{{ __('Suppliers & Customers') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.suppliers.*') ? 'active' : '' }}" href="{{ route('tenant.suppliers.index') }}">{{ __('Suppliers & Aarthi') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}" href="{{ route('tenant.customers.index') }}">{{ __('Customers & Accounts') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.purchases.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.purchases.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Purchases') }}">
          <i class="ph-duotone ph-truck"></i>
          <span>{{ __('Mandi Purchases') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.purchases.index') ? 'active' : '' }}" href="{{ route('tenant.purchases.index') }}">{{ __('Purchase Invoices') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.purchases.create') ? 'active' : '' }}" href="{{ route('tenant.purchases.create') }}">{{ __('New Mandi Purchase') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.suppliers.*') ? 'active' : '' }}" href="{{ route('tenant.suppliers.index') }}">{{ __('Suppliers (Aarthi)') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.inventory.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.inventory.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Inventory') }}">
          <i class="ph-duotone ph-package"></i>
          <span>{{ __('Inventory & Stock') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.index') ? 'active' : '' }}" href="{{ route('tenant.inventory.index') }}">{{ __('Current Stock Levels') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.batches') ? 'active' : '' }}" href="{{ route('tenant.inventory.batches') }}">{{ __('Produce Lots & Batches') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.movements') ? 'active' : '' }}" href="{{ route('tenant.inventory.movements') }}">{{ __('Stock Movement Ledger') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.transfers.*') ? 'active' : '' }}" href="{{ route('tenant.inventory.transfers.index') }}">{{ __('Branch Transfers') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.adjustments.*') ? 'active' : '' }}" href="{{ route('tenant.inventory.adjustments.index') }}">{{ __('Physical Audit Adjustments') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.wastage.*') ? 'active' : '' }}" href="{{ route('tenant.inventory.wastage.index') }}">{{ __('Wastage & Spoilage Loss') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.inventory.regradings.*') ? 'active' : '' }}" href="{{ route('tenant.inventory.regradings.index') }}">{{ __('Produce Sorting & Regrading') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.expenses.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.expenses.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Expenses') }}">
          <i class="ph-duotone ph-wallet"></i>
          <span>{{ __('Expenses & Petty Cash') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.expenses.index') ? 'active' : '' }}" href="{{ route('tenant.expenses.index') }}">{{ __('All Expenses') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.expenses.create') ? 'active' : '' }}" href="{{ route('tenant.expenses.create') }}">{{ __('Record Expense') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.accounting.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.accounting.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Accounting & Finance') }}">
          <i class="ph-duotone ph-book-open"></i>
          <span>{{ __('Accounting & Ledgers') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.coa.*') ? 'active' : '' }}" href="{{ route('tenant.accounting.coa.index') }}">{{ __('Chart of Accounts') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.journal.*') ? 'active' : '' }}" href="{{ route('tenant.accounting.journal.index') }}">{{ __('Journal Vouchers (JV)') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.ledger') ? 'active' : '' }}" href="{{ route('tenant.accounting.ledger') }}">{{ __('General Ledger') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.trial-balance') ? 'active' : '' }}" href="{{ route('tenant.accounting.trial-balance') }}">{{ __('Trial Balance') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.pnl') ? 'active' : '' }}" href="{{ route('tenant.accounting.pnl') }}">{{ __('Profit & Loss (P&L)') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.balance-sheet') ? 'active' : '' }}" href="{{ route('tenant.accounting.balance-sheet') }}">{{ __('Balance Sheet') }}</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.reports.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.reports.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="{{ __('Reports') }}">
          <i class="ph-duotone ph-chart-bar"></i>
          <span>{{ __('Reports & Analytics') }}</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.reports.index') ? 'active' : '' }}" href="{{ route('tenant.reports.index') }}">{{ __('Reports Hub') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.reports.sales') ? 'active' : '' }}" href="{{ route('tenant.reports.sales') }}">{{ __('Sales Analytics') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.reports.purchases') ? 'active' : '' }}" href="{{ route('tenant.reports.purchases') }}">{{ __('Mandi Purchases') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.reports.day-end') ? 'active' : '' }}" href="{{ route('tenant.reports.day-end') }}">{{ __('Daily Z-Report (روزانہ اختتامی رپورٹ)') }}</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.accounting.pnl') ? 'active' : '' }}" href="{{ route('tenant.accounting.pnl') }}">{{ __('Profit & Loss') }}</a></li>
        </ul>
      </li>

      <!-- Settings & Administration -->
      <li class="nav-heading"><span>System & Staff</span></li>

      <li class="nav-item has-submenu {{ request()->routeIs('tenant.users.*') || request()->routeIs('tenant.roles.*') || request()->routeIs('tenant.audit.*') ? 'show' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.users.*') || request()->routeIs('tenant.roles.*') || request()->routeIs('tenant.audit.*') ? 'active' : '' }}" href="#" aria-expanded="false" data-tooltip="Staff & Roles">
          <i class="ph-duotone ph-shield-check"></i>
          <span>Staff & Access</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link {{ request()->routeIs('tenant.users.*') ? 'active' : '' }}" href="{{ route('tenant.users.index') }}">Staff Members</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.roles.*') ? 'active' : '' }}" href="{{ route('tenant.roles.index') }}">Roles & Permissions</a></li>
          <li><a class="nav-link {{ request()->routeIs('tenant.audit.*') ? 'active' : '' }}" href="{{ route('tenant.audit.index') }}">Security Audit Trail</a></li>
        </ul>
      </li>

      <li class="nav-item {{ request()->routeIs('tenant.hardware.*') ? 'active' : '' }}">
        <a class="nav-link {{ request()->routeIs('tenant.hardware.*') ? 'active' : '' }}" href="{{ route('tenant.hardware.index') }}" data-tooltip="{{ __('Hardware & Scales') }}">
          <i class="ph-duotone ph-cpu"></i>
          <span>{{ __('Hardware & Scales') }}</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="#" data-tooltip="Settings">
          <i class="ph-duotone ph-gear"></i>
          <span>{{ __('common.settings') }}</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>

