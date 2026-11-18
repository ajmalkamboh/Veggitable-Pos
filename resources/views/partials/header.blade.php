<!-- Header -->
<header class="header">
  <!-- Header Left -->
  <div class="header-left d-flex align-items-center gap-2">
    <button class="sidebar-toggle" type="button" title="Toggle Sidebar" aria-label="Toggle Sidebar">
      <i class="bi bi-list fs-4"></i>
    </button>
    <a href="{{ url('/') }}" class="header-logo d-flex align-items-center gap-2 text-decoration-none">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable SaaS" height="28">
      <span class="fw-bold fs-5 text-dark">{{ __('common.app_name') }}</span>
    </a>
  </div>

  <!-- Header Search (Desktop) - Expandable -->
  <div class="header-search">
    <form class="search-form collapsed" action="#" method="GET">
      <button type="button" class="search-toggle-btn" aria-label="{{ __('common.search') }}">
        <i class="bi bi-search"></i>
      </button>
      <input type="search" name="q" placeholder="{{ __('common.search') }}" autocomplete="off">
    </form>
  </div>

  <!-- Header Right -->
  <div class="header-right ms-auto d-flex align-items-center gap-2">
    <div class="header-actions-desktop d-flex align-items-center gap-2">
      <!-- Active Business Context -->
      @inject('tenantContext', 'App\Core\MultiTenancy\TenantContext')
      <div class="header-action dropdown">
        <button class="btn btn-sm btn-outline-primary rounded-pill dropdown-toggle d-flex align-items-center gap-2 px-3 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch Business">
          <i class="bi bi-shop text-primary"></i>
          <span class="d-none d-sm-inline fw-semibold text-truncate" style="max-width: 150px;">
            {{ $tenantContext->getTenant()?->name ?? 'Select Business' }}
          </span>
          @if($tenantContext->getBranch())
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill d-none d-lg-inline" style="font-size: 0.7rem;">
              {{ $tenantContext->getBranch()->code }}
            </span>
          @endif
        </button>
        @php
          $availableTenants = \Illuminate\Support\Facades\Schema::hasTable('tenants')
              ? \App\Models\Tenant::where('status', 'active')->get()
              : collect();
        @endphp
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 py-2" style="min-width: 240px;">
          <li class="dropdown-header small text-muted text-uppercase fw-semibold px-3 py-1">
            <i class="bi bi-arrow-repeat me-1"></i> Switch Business
          </li>
          @forelse($availableTenants as $t)
            <li>
              <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 {{ $tenantContext->getTenantId() === $t->id ? 'active' : '' }}" href="{{ route('tenant.switch', $t->id) }}">
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i class="bi bi-building text-muted"></i>
                  <span class="fw-medium text-truncate">{{ $t->name }}</span>
                </div>
                @if($tenantContext->getTenantId() === $t->id)
                  <i class="bi bi-check-circle-fill text-success ms-2"></i>
                @endif
              </a>
            </li>
          @empty
            <li class="px-3 py-2 text-muted small">No businesses available</li>
          @endforelse
          <li><hr class="dropdown-divider my-2"></li>
          <li>
            <a class="dropdown-item text-primary fw-medium px-3 py-2" href="{{ route('tenant.register.create') }}">
              <i class="bi bi-plus-circle me-1"></i> Register New Business
            </a>
          </li>
        </ul>
      </div>

      <!-- Quick POS Access Button (for shop staff / owner / admin) -->
      @if(auth()->check() && (auth()->user()->tenant_id || auth()->user()->isSuperAdmin()))
        <div class="header-action d-none d-md-flex">
          <a href="{{ route('tenant.pos.counter') }}" class="btn btn-sm btn-success rounded-pill d-inline-flex align-items-center gap-1 px-3 shadow-sm text-white text-decoration-none" title="Quick POS Checkout">
            <i class="bi bi-cart-check"></i>
            <span class="fw-semibold">POS</span>
          </a>
        </div>
      @endif

      <!-- Language Switcher -->
      <div class="header-action dropdown">
        <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle d-flex align-items-center gap-1 px-2 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Language / زبان">
          <i class="bi bi-translate text-secondary"></i>
          <span class="fw-medium">{{ app()->getLocale() === 'ur' ? 'اردو' : 'English' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 py-1" style="min-width: 140px;">
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('language.switch', 'en') }}">
              <span class="fw-medium">English</span>
              @if(app()->getLocale() === 'en')
                <i class="bi bi-check-circle-fill text-success ms-2"></i>
              @endif
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 {{ app()->getLocale() === 'ur' ? 'active' : '' }}" href="{{ route('language.switch', 'ur') }}">
              <span class="urdu-font fw-medium">اردو</span>
              @if(app()->getLocale() === 'ur')
                <i class="bi bi-check-circle-fill text-success ms-2"></i>
              @endif
            </a>
          </li>
        </ul>
      </div>

      <!-- Theme Toggle -->
      <button class="header-action theme-toggle rounded-circle" type="button" title="{{ __('common.theme') }}" aria-label="Toggle Theme">
        <i class="bi bi-moon icon-dark"></i>
        <i class="bi bi-sun icon-light"></i>
      </button>

      <!-- Fullscreen Toggle -->
      <button class="header-action fullscreen-toggle rounded-circle d-none d-md-inline-flex" type="button" onclick="if (typeof toggleFullscreen === 'function') toggleFullscreen();" title="Fullscreen" aria-label="Toggle Fullscreen">
        <i class="bi bi-fullscreen icon-enter"></i>
        <i class="bi bi-fullscreen-exit icon-exit"></i>
      </button>

      <!-- Notifications Dropdown -->
      @php
        $unreadNotifCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
        $recentNotifs = auth()->check() ? auth()->user()->unreadNotifications()->limit(5)->get() : collect();
      @endphp
      <div class="header-action dropdown notification-dropdown">
        <button class="dropdown-toggle position-relative rounded-circle border-0 bg-transparent p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('common.notifications') }}" aria-label="Notifications">
          <i class="bi bi-bell fs-5"></i>
          @if($unreadNotifCount > 0)
            <span class="position-absolute badge rounded-pill bg-danger border border-white" style="top: 2px; right: 2px; font-size: 0.65rem; padding: 0.25em 0.45em;">
              {{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}
            </span>
          @endif
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 p-0" style="width: 340px; max-width: calc(100vw - 2rem);">
          <div class="notification-header d-flex justify-content-between align-items-center p-3 border-bottom bg-light rounded-top-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-bell-fill text-primary"></i>
              <h6 class="mb-0 fw-bold">{{ __('common.notifications') }}</h6>
            </div>
            @if(auth()->check() && auth()->user()->tenant_id)
              <a href="{{ route('tenant.notifications.index') }}" class="small text-primary text-decoration-none fw-medium">{{ __('View All') }}</a>
            @endif
          </div>
          <div class="notification-list p-2" style="max-height: 320px; overflow-y: auto;">
            @forelse($recentNotifs as $rn)
              <a href="{{ route('tenant.notifications.index') }}" class="d-block p-2 text-decoration-none text-dark border-bottom rounded-2">
                <div class="fw-semibold small text-truncate text-dark">{{ $rn->data['title'] ?? 'Alert' }}</div>
                <div class="text-muted small text-truncate" style="font-size: 11px;">{{ $rn->data['message'] ?? '' }}</div>
              </a>
            @empty
              <div class="p-4 text-center text-muted small">
                <i class="bi bi-check2-circle fs-3 d-block text-success mb-1"></i>
                {{ __('No unread notifications') }}
              </div>
            @endforelse
          </div>
          @if(auth()->check() && auth()->user()->tenant_id)
            <div class="p-2 border-top text-center bg-light rounded-bottom-3">
              <a href="{{ route('tenant.notifications.index') }}" class="small text-decoration-none fw-semibold text-primary">
                {{ __('common.all_notifications') ?? 'All Notifications' }} <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          @endif
        </div>
      </div>

      <!-- User Dropdown -->
      @auth
        <div class="header-action dropdown user-dropdown">
          <button class="dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent p-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Menu">
            <img src="{{ asset('assets/img/profile-img.webp') }}" alt="{{ auth()->user()->name }}" class="avatar rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
            <div class="d-none d-lg-flex flex-column text-start" style="line-height: 1.2;">
              <span class="fw-semibold text-dark small text-truncate" style="max-width: 120px;">{{ auth()->user()->name }}</span>
              <span class="text-muted" style="font-size: 0.7rem;">
                {{ auth()->user()->isSuperAdmin() ? 'Super Admin' : (auth()->user()->is_tenant_owner ? 'Owner' : 'Staff') }}
              </span>
            </div>
            <i class="bi bi-chevron-down text-muted small d-none d-lg-inline"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 py-2" style="min-width: 220px;">
            <li class="dropdown-header px-3 py-2 border-bottom mb-1">
              <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
              <div class="small text-muted text-truncate">{{ auth()->user()->email }}</div>
            </li>
            @if(auth()->user()->isSuperAdmin())
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('superadmin.dashboard') }}">
                  <i class="bi bi-speedometer2 text-primary"></i> Super Admin Dashboard
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('superadmin.health.index') }}">
                  <i class="bi bi-heart-pulse text-success"></i> System Health
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('superadmin.backups.index') }}">
                  <i class="bi bi-shield-check text-info"></i> Backups & Security
                </a>
              </li>
            @else
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('dashboard') }}">
                  <i class="bi bi-speedometer2 text-primary"></i> Business Dashboard
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('tenant.pos.counter') }}">
                  <i class="bi bi-cart-check text-success"></i> POS Cashier Terminal
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('tenant.users.index') }}">
                  <i class="bi bi-people text-secondary"></i> Staff & Team
                </a>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 px-3 py-2" href="{{ route('tenant.backup.index') }}">
                  <i class="bi bi-cloud-arrow-down text-info"></i> Data Export & Backup
                </a>
              </li>
            @endif
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-item-danger d-flex align-items-center gap-2 px-3 py-2 text-danger border-0 bg-transparent w-100 text-start">
                  <i class="bi bi-box-arrow-right"></i> {{ __('common.logout') }}
                </button>
              </form>
            </li>
          </ul>
        </div>
      @else
        <div class="header-action">
          <a href="{{ route('login') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>{{ __('common.login') }}</span>
          </a>
        </div>
      @endauth
    </div>
  </div>
</header>
