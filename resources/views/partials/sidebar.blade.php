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
      <li class="nav-heading"><span>SaaS Platform</span></li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="SaaS Management">
          <i class="ph-duotone ph-buildings"></i>
          <span>Tenants & Businesses</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">All Businesses</a></li>
          <li><a class="nav-link" href="#">Register New Tenant</a></li>
          <li><a class="nav-link" href="#">Tenant Activity</a></li>
        </ul>
      </li>

      <li class="nav-item has-submenu">
        <a class="nav-link" href="#" aria-expanded="false" data-tooltip="Subscriptions">
          <i class="ph-duotone ph-credit-card"></i>
          <span>Subscription Plans</span>
          <i class="ph-duotone ph-caret-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu">
          <li><a class="nav-link" href="#">Plans & Pricing</a></li>
          <li><a class="nav-link" href="#">Active Subscriptions</a></li>
          <li><a class="nav-link" href="#">Payment History</a></li>
        </ul>
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
