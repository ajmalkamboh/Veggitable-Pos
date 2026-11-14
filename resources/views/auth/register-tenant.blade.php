@extends('layouts.auth')

@section('title', 'Register Your Vegetable Business - ' . config('app.name'))

@section('content')
<div class="auth-card p-4">
    <div class="auth-card-header text-center mb-4">
        <h2 class="auth-title fw-bold fs-4">Register Your Vegetable Business</h2>
        <p class="auth-subtitle text-muted small">Start your 14-day free trial. Setup your shop, branches, inventory, and POS in minutes.</p>
    </div>

    <form method="POST" action="{{ route('tenant.register.store') }}" novalidate>
        @csrf

        <h6 class="text-primary fw-semibold border-bottom pb-2 mb-3">
            <i class="bi bi-shop me-1"></i> Business Details
        </h6>

        <div class="row g-2">
            <div class="col-md-7">
                <x-form-input 
                    name="business_name" 
                    label="Business / Shop Name" 
                    placeholder="e.g. Al-Madina Fresh Vegetables" 
                    required 
                />
            </div>
            <div class="col-md-5">
                <x-form-input 
                    name="slug" 
                    label="URL Slug (Optional)" 
                    placeholder="e.g. al-madina" 
                    help="Your shop link: veggitable.test/t/your-slug"
                />
            </div>
        </div>

        <div class="row g-2">
            <div class="col-md-6">
                <x-form-input 
                    name="phone" 
                    label="Phone Number" 
                    placeholder="e.g. +92 300 1234567" 
                />
            </div>
            <div class="col-md-6">
                <x-form-input 
                    name="city" 
                    label="City" 
                    placeholder="e.g. Lahore / Karachi" 
                />
            </div>
        </div>

        <div class="row g-2">
            <div class="col-md-6">
                <x-form-select 
                    name="currency" 
                    label="Currency" 
                    :options="['PKR' => 'PKR - Pakistani Rupee', 'USD' => 'USD - US Dollar', 'SAR' => 'SAR - Saudi Riyal', 'AED' => 'AED - UAE Dirham']"
                    selected="PKR"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-form-select 
                    name="default_locale" 
                    label="Default Language" 
                    :options="['en' => 'English', 'ur' => 'اردو (Urdu RTL)']"
                    selected="en"
                    required
                />
            </div>
        </div>

        <h6 class="text-primary fw-semibold border-bottom pb-2 mt-4 mb-3">
            <i class="bi bi-person-badge me-1"></i> Business Owner Account
        </h6>

        <x-form-input 
            name="owner_name" 
            label="Owner Full Name" 
            placeholder="e.g. Haji Muhammad" 
            required 
        />

        <x-form-input 
            type="email" 
            name="owner_email" 
            label="Email Address" 
            placeholder="owner@example.com" 
            required 
        />

        <div class="row g-2">
            <div class="col-md-6">
                <x-form-input 
                    type="password" 
                    name="owner_password" 
                    label="Password" 
                    placeholder="At least 8 characters" 
                    required 
                />
            </div>
            <div class="col-md-6">
                <x-form-input 
                    type="password" 
                    name="owner_password_confirmation" 
                    label="Confirm Password" 
                    placeholder="Repeat password" 
                    required 
                />
            </div>
        </div>

        <div class="mt-4">
            <x-button type="submit" variant="primary" class="w-100 py-2 fs-6">
                <i class="bi bi-check2-circle me-1"></i> Register Business & Start Free Trial
            </x-button>
        </div>
    </form>
</div>
@endsection
