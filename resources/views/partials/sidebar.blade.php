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
        <a class="nav-link" href="#" data-tooltip="POS Counter">
          <i class="ph-duotone ph-shopping-cart"></i>
          <span>POS Cashier</span>
        </a>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Products">
          <i class="ph-duotone ph-plant"></i>
          <span>Vegetables & Fruits</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Product List</a></li>
          <li><a class="nav-link" href="#">Categories</a></li>
          <li><a class="nav-link" href="#">Units & Conversions</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Purchases">
          <i class="ph-duotone ph-truck"></i>
          <span>Purchases</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Purchase Invoices</a></li>
          <li><a class="nav-link" href="#">New Purchase</a></li>
          <li><a class="nav-link" href="#">Suppliers</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Inventory">
          <i class="ph-duotone ph-package"></i>
          <span>Inventory & Batches</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Current Stock</a></li>
          <li><a class="nav-link" href="#">Stock Movement Ledger</a></li>
          <li><a class="nav-link" href="#">Stock Valuation</a></li>
          <li><a class="nav-link" href="#">Wastage & Damage</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Accounts">
          <i class="ph-duotone ph-book-open"></i>
          <span>Accounting & Finance</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Chart of Accounts</a></li>
          <li><a class="nav-link" href="#">Journal Vouchers</a></li>
          <li><a class="nav-link" href="#">General Ledger</a></li>
          <li><a class="nav-link" href="#">Trial Balance</a></li>
          <li><a class="nav-link" href="#">Profit & Loss</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Reports">
          <i class="ph-duotone ph-chart-bar"></i>
          <span>Reports</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Sales Report</a></li>
          <li><a class="nav-link" href="#">Purchase Report</a></li>
          <li><a class="nav-link" href="#">Inventory Report</a></li>
          <li><a class="nav-link" href="#">Financial Statements</a></li>
        </ul>
      </li>

      <!-- Settings & Administration -->
      <li class="nav-heading"><span>System</span></li>

      <li class="nav-item">
        <a class="nav-link" href="#" data-tooltip="Roles & Access">
          <i class="ph-duotone ph-shield-check"></i>
          <span>Roles & Permissions</span>
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

