@extends('layouts.auth')

@section('title', 'Set New Password - Veggitable SaaS')

@section('content')
  <div class="text-center mb-4">
    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable" height="40">
      <span class="fs-4 fw-bold text-dark">Veggitable SaaS</span>
    </a>
    <h5 class="fw-bold mb-1">Set New Password</h5>
    <p class="text-muted small">Choose a secure password for your account</p>
  </div>

  <form method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
      <label for="email" class="form-label small fw-semibold">Email Address</label>
      <input 
        type="email" 
        name="email" 
        id="email" 
        class="form-control @error('email') is-invalid @enderror" 
        value="{{ old('email', $email) }}" 
        required 
        readonly
      >
      @error('email')
        <div class="text-danger small mt-1">{{ $message }}</div>
      @enderror
    </div>

    <div class="mb-3">
      <label for="password" class="form-label small fw-semibold">New Password</label>
      <input 
        type="password" 
        name="password" 
        id="password" 
        class="form-control @error('password') is-invalid @enderror" 
        placeholder="Minimum 8 characters with letters & numbers" 
        required 
        autofocus
      >
      @error('password')
        <div class="text-danger small mt-1">{{ $message }}</div>
      @enderror
    </div>

    <div class="mb-4">
      <label for="password_confirmation" class="form-label small fw-semibold">Confirm New Password</label>
      <input 
        type="password" 
        name="password_confirmation" 
        id="password_confirmation" 
        class="form-control" 
        placeholder="Re-enter your new password" 
        required
      >
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-shield-check"></i>
      <span>Reset & Save Password</span>
    </button>
  </form>

  <div class="border-top mt-4 pt-3 text-center">
    <a href="{{ route('login') }}" class="small fw-semibold text-decoration-none text-muted">
      <i class="bi bi-arrow-left me-1"></i> Back to Sign In
    </a>
  </div>
@endsection

