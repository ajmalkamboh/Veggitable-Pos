<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', __('common.login')) - {{ config('app.name', 'Veggitable SaaS') }}</title>

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  @if(app()->getLocale() === 'ur')
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
  @else
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  @endif

  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

  <!-- Template Main CSS -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">

  @if(app()->getLocale() === 'ur')
    <link href="{{ asset('assets/css/custom-rtl.css') }}" rel="stylesheet">
  @endif

  @livewireStyles
  @stack('styles')
</head>

<body>
  <div class="auth-layout">
    <div class="auth-container">
      <!-- Logo -->
      <a href="{{ url('/') }}" class="auth-logo">
        <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable SaaS">
        <span>{{ __('common.app_name') }}</span>
      </a>

      @include('components.alert')

      @yield('content')
      {{ $slot ?? '' }}

      <!-- Language selector for auth pages -->
      <div class="text-center mt-3">
        <a href="{{ route('language.switch', 'en') }}" class="btn btn-sm btn-link {{ app()->getLocale() === 'en' ? 'fw-bold' : 'text-muted' }}">English</a>
        <span class="text-muted">|</span>
        <a href="{{ route('language.switch', 'ur') }}" class="btn btn-sm btn-link urdu-font {{ app()->getLocale() === 'ur' ? 'fw-bold' : 'text-muted' }}">اردو</a>
      </div>
    </div>
  </div>

  <!-- Vendor JS -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/theme.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>

  @livewireScripts
  @stack('scripts')
</body>

</html>
