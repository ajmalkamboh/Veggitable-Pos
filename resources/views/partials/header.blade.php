<!-- Header -->
<header class="header">
  <!-- Header Left -->
  <div class="header-left">
    <a href="{{ url('/') }}" class="header-logo">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable SaaS">
      <span>{{ __('common.app_name') }}</span>
    </a>
    <button class="sidebar-toggle" title="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>
  </div>

  <!-- Header Search (Desktop) - Expandable -->
  <div class="header-search">
    <form class="search-form collapsed" action="#" method="GET">
      <button type="button" class="search-toggle-btn"><i class="bi bi-search"></i></button>
      <input type="search" name="q" placeholder="{{ __('common.search') }}" autocomplete="off">
    </form>
  </div>

  <!-- Header Right -->
  <div class="header-right">
    <div class="header-actions-desktop">
      <!-- Active Business Context -->
      @inject('tenantContext', 'App\Core\MultiTenancy\TenantContext')
      <div class="header-action dropdown">
        <button class="dropdown-toggle btn btn-sm btn-outline-primary d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-shop"></i>
          <span class="d-none d-md-inline">{{ $tenantContext->getTenant()?->name ?? 'Select Business' }}</span>
        </button>
        @php
          $availableTenants = \Illuminate\Support\Facades\Schema::hasTable('tenants')
              ? \App\Models\Tenant::where('status', 'active')->get()
              : collect();
        @endphp
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li class="dropdown-header small text-muted">Switch Business Context</li>
          @foreach($availableTenants as $t)
            <li>
              <a class="dropdown-item d-flex align-items-center justify-content-between {{ $tenantContext->getTenantId() === $t->id ? 'active' : '' }}" href="{{ route('tenant.switch', $t->id) }}">
                <span>{{ $t->name }}</span>
                @if($tenantContext->getTenantId() === $t->id)
                  <i class="bi bi-check-lg text-success"></i>
                @endif
              </a>
            </li>
          @endforeach
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-primary" href="{{ route('tenant.register.create') }}">
              <i class="bi bi-plus-circle me-1"></i> Register New Business
            </a>
          </li>
        </ul>
      </div>

      <!-- Language Switcher -->
      <div class="header-action dropdown">
        <button class="dropdown-toggle btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-translate"></i>
          <span>{{ app()->getLocale() === 'ur' ? 'اردو' : 'English' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('language.switch', 'en') }}">
              <span>English</span>
              @if(app()->getLocale() === 'en')
                <i class="bi bi-check-lg text-success"></i>
              @endif
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between {{ app()->getLocale() === 'ur' ? 'active' : '' }}" href="{{ route('language.switch', 'ur') }}">
              <span class="urdu-font">اردو</span>
              @if(app()->getLocale() === 'ur')
                <i class="bi bi-check-lg text-success"></i>
              @endif
            </a>
          </li>
        </ul>
      </div>

      <!-- Theme Toggle -->
      <button class="header-action theme-toggle" title="{{ __('common.theme') }}">
        <i class="bi bi-moon icon-dark"></i>
        <i class="bi bi-sun icon-light"></i>
      </button>

      <!-- Fullscreen Toggle -->
      <button class="header-action fullscreen-toggle" onclick="if (typeof toggleFullscreen === 'function') toggleFullscreen();" title="Fullscreen">
        <i class="bi bi-fullscreen icon-enter"></i>
        <i class="bi bi-fullscreen-exit icon-exit"></i>
      </button>

      <!-- Notifications Dropdown -->
      <div class="header-action dropdown notification-dropdown">
        <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell"></i>
          <span class="badge">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
          <div class="notification-header">
            <h6>{{ __('common.notifications') }}</h6>
          </div>
          <div class="notification-list p-3 text-center text-muted small">
            No unread notifications
          </div>
        </div>
      </div>

      <!-- User Dropdown -->
      @auth
        <div class="header-action dropdown user-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="{{ asset('assets/img/profile-img.webp') }}" alt="User" class="avatar">
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="dropdown-header">
              <img src="{{ asset('assets/img/profile-img.webp') }}" alt="User" class="user-avatar">
              <div class="user-info">
                <h6>{{ auth()->user()->name }}</h6>
                <span class="small text-muted">{{ auth()->user()->email }}</span>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            @if(auth()->user()->isSuperAdmin())
              <li>
                <a class="dropdown-item" href="{{ route('superadmin.dashboard') }}">
                  <i class="bi bi-speedometer2"></i> Super Admin Dashboard
                </a>
              </li>
            @else
              <li>
                <a class="dropdown-item" href="{{ route('tenant.users.index') }}">
                  <i class="bi bi-people"></i> Staff & Team
                </a>
              </li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-item-danger d-flex align-items-center gap-2">
                  <i class="bi bi-box-arrow-right"></i> {{ __('common.logout') }}
                </button>
              </form>
            </li>
          </ul>
        </div>
      @else
        <div class="header-action">
          <a href="{{ route('login') }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>{{ __('common.login') }}</span>
          </a>
        </div>
      @endauth
    </div>
  </div>
</header>

