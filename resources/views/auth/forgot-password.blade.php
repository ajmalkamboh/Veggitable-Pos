@extends('layouts.auth')

@section('title', 'Forgot Password - Veggitable SaaS')

@section('content')
  <div class="text-center mb-4">
    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
      <img src="{{ asset('assets/img/logo.webp') }}" alt="Veggitable" height="40">
      <span class="fs-4 fw-bold text-dark">Veggitable SaaS</span>
    </a>
    <h5 class="fw-bold mb-1">Reset Password</h5>
    <p class="text-muted small">Enter your email and we'll send you instructions to reset your password.</p>
  </div>

  @if(session('status'))
    <div class="alert alert-info py-2 px-3 small mb-3">
      <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
    </div>
  @endif

  @if(session('demo_reset_link'))
    <div class="alert alert-warning py-2 px-3 small mb-3">
      <div class="fw-bold mb-1"><i class="bi bi-key-fill me-1"></i> Local Development Reset Link:</div>
      <a href="{{ session('demo_reset_link') }}" class="text-break small fw-semibold">Click here to reset password</a>
    </div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-4">
      <label for="email" class="form-label small fw-semibold">Registered Email Address</label>
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

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-envelope-paper"></i>
      <span>Send Reset Instructions</span>
    </button>
  </form>

  <div class="border-top mt-4 pt-3 text-center">
    <a href="{{ route('login') }}" class="small fw-semibold text-decoration-none text-muted">
      <i class="bi bi-arrow-left me-1"></i> Back to Sign In
    </a>
  </div>
@endsection

