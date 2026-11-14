@extends('layouts.auth')

@section('title', 'Sign In - Veggitable SaaS')

@section('content')
  <!-- Card Header / Brand -->
  <div class="text-center mb-4">
    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable" height="40">
      <span class="fs-4 fw-bold text-dark">Veggitable SaaS</span>
    </a>
    <h5 class="fw-bold mb-1">Welcome Back</h5>
    <p class="text-muted small">Sign in to your vegetable shop management portal</p>
  </div>

  @if(session('error'))
    <div class="alert alert-danger py-2 px-3 small mb-3">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success py-2 px-3 small mb-3">
      <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
  @endif

  <!-- Login Form -->
  <form method="POST" action="{{ route('login.store') }}">
    @csrf

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label small fw-semibold">Email Address</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
        <input 
          type="email" 
          name="email" 
          id="email" 
          class="form-control border-start-0 @error('email') is-invalid @enderror" 
          value="{{ old('email') }}" 
          placeholder="e.g. owner@almadina.test" 
          required 
          autofocus
        >
      </div>
      @error('email')
        <div class="text-danger small mt-1">{{ $message }}</div>
      @enderror
    </div>

    <!-- Password -->
    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="password" class="form-label small fw-semibold mb-0">Password</label>
        <a href="{{ route('password.request') }}" class="small text-decoration-none text-primary">Forgot Password?</a>
      </div>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
        <input 
          type="password" 
          name="password" 
          id="password" 
          class="form-control border-start-0 @error('password') is-invalid @enderror" 
          placeholder="Enter your password" 
          required
        >
      </div>
      @error('password')
        <div class="text-danger small mt-1">{{ $message }}</div>
      @enderror
    </div>

    <!-- Remember Me -->
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
      <label class="form-check-label small text-muted" for="remember">
        Keep me signed in on this device
      </label>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-box-arrow-in-right"></i>
      <span>Sign In to Shop</span>
    </button>
  </form>

  <!-- Register Business Prompt -->
  <div class="border-top mt-4 pt-3 text-center">
    <p class="text-muted small mb-0">
      New vegetable business? 
      <a href="{{ route('tenant.register.create') }}" class="fw-semibold text-primary text-decoration-none">Register New Business</a>
    </p>
  </div>
@endsection

