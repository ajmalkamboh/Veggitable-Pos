@extends('layouts.admin')

@section('title', __('common.dashboard') . ' - ' . __('common.tagline'))

@section('content')
  <x-breadcrumb 
    :title="__('common.dashboard')" 
    :items="[
      'Phase 01: Technical Foundation' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('language.switch', app()->getLocale() === 'en' ? 'ur' : 'en') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-translate"></i>
        <span>Switch to {{ app()->getLocale() === 'en' ? 'اردو (Urdu RTL)' : 'English (LTR)' }}</span>
      </a>
      <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#archInfoModal">
        <i class="bi bi-info-circle"></i>
        <span>Architecture Spec</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- KPI Overview Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Environment" 
        value="PHP 8.3 & L12" 
        icon="bi-cpu" 
        color="primary"
        subtitle="MySQL 8.4 + Livewire"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Admin Theme" 
        value="EasyAdmin Pro" 
        icon="bi-palette" 
        color="success"
        subtitle="Bootstrap 5.3 + Custom RTL"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Bilingual Engine" 
        value="{{ app()->getLocale() === 'ur' ? 'اردو (فعال)' : 'English (Active)' }}" 
        icon="bi-translate" 
        color="warning"
        subtitle="English & Urdu RTL/LTR"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Phase Status" 
        value="Phase 01 Done" 
        icon="bi-check2-circle" 
        color="info"
        change="100%"
        changeType="positive"
        subtitle="Foundation & Architecture"
      />
    </div>
  </div>

  <!-- Main System Info & Livewire Component -->
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <x-card title="System Foundation & Technical Architecture" subtitle="Verified technology stack, database connections, and SaaS blueprints">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-primary"><i class="bi bi-layers me-2"></i>Multi-Tenant Architecture Plan</h6>
              <p class="small text-muted mb-0">Shared MySQL database with tenant isolation (<code>tenant_id</code>), automated tenant global scopes, multi-branch hierarchy, and policy authorization.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-success"><i class="bi bi-calculator me-2"></i>Double-Entry Accounting & FIFO Stock</h6>
              <p class="small text-muted mb-0">Double-entry accounting ledger engine (balanced Debits = Credits) integrated with FIFO batch costing for vegetable perishables and wastage.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-warning"><i class="bi bi-translate me-2"></i>Bilingual LTR / RTL Localisation</h6>
              <p class="small text-muted mb-0">Full English and Urdu support with Noto Nastaliq Urdu typography, Bootstrap RTL, dynamic direction switching, and locale persistence.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-info"><i class="bi bi-file-earmark-pdf me-2"></i>Reporting & Printing Subsystems</h6>
              <p class="small text-muted mb-0">Integrated Barryvdh DomPDF and Maatwebsite Excel packages for 80mm thermal receipts, A4 invoices, and financial reports.</p>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="fw-bold mb-3">Core Technical Specifications</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Component</th>
                <th>Technology / Engine</th>
                <th>Status</th>
                <th>Integration Note</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Framework</strong></td>
                <td>Laravel Framework 13 / 12 Core</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Bootstrap 5 + Modular Services</td>
              </tr>
              <tr>
                <td><strong>Runtime</strong></td>
                <td>PHP 8.3.28 ZTS x64</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>OPcache & Strict Types enabled</td>
              </tr>
              <tr>
                <td><strong>Database Engine</strong></td>
                <td>MySQL 8.4.7 via WAMP</td>
                <td><span class="badge bg-success-subtle text-success">Connected</span></td>
                <td>Database: <code>veggitable_saas</code> (utf8mb4)</td>
              </tr>
              <tr>
                <td><strong>Reactive Frontend</strong></td>
                <td>Livewire 4 / 3 Reactive Engine</td>
                <td><span class="badge bg-success-subtle text-success">Configured</span></td>
                <td>No React / Vue / Inertia / Tailwind</td>
              </tr>
              <tr>
                <td><strong>Admin UI Framework</strong></td>
                <td>EasyAdmin Pro (BootstrapMade)</td>
                <td><span class="badge bg-success-subtle text-success">Installed</span></td>
                <td>Integrated into Blade reusable components</td>
              </tr>
              <tr>
                <td><strong>Document Engines</strong></td>
                <td>DomPDF 3.1 & PhpSpreadsheet 5.11</td>
                <td><span class="badge bg-success-subtle text-success">Installed</span></td>
                <td>Ready for Thermal receipts & Excel exports</td>
              </tr>
            </tbody>
          </table>
        </div>
      </x-card>
    </div>

    <!-- Livewire Foundation Test Widget -->
    <div class="col-lg-4">
      <x-card title="Livewire Reactive Foundation Test" subtitle="Testing real-time state manipulation">
        @livewire('architecture-overview')
      </x-card>

      <x-card title="Language & RTL Verification" subtitle="Check English / Urdu text shaping">
        <div class="p-3 bg-light rounded mb-3">
          <div class="text-muted small">English:</div>
          <div class="fw-semibold">Fresh Tomatoes - Grade A (Rs. 120 / KG)</div>
          <hr class="my-2">
          <div class="text-muted small">اردو (Urdu Nastaliq):</div>
          <div class="fw-bold urdu-font fs-5 text-success">تازہ ٹماٹر - درجہ اول (120 روپے فی کلو)</div>
        </div>
        <a href="{{ route('language.switch', app()->getLocale() === 'en' ? 'ur' : 'en') }}" class="btn btn-outline-primary btn-sm w-100">
          <i class="bi bi-arrow-repeat me-1"></i>
          Toggle Current Direction ({{ app()->getLocale() === 'en' ? 'Switch to RTL Urdu' : 'Switch to LTR English' }})
        </a>
      </x-card>
    </div>
  </div>

  <!-- Architecture Modal -->
  <x-modal id="archInfoModal" title="System Architecture & Database Conventions" size="lg">
    <div class="p-2">
      <h6 class="fw-bold text-primary">Master Phase Roadmap</h6>
      <p class="small text-muted">The system is architected across 20 structured phases adhering strictly to enterprise SaaS engineering, multi-tenant data isolation, double-entry financial integrity, and bilingual accessibility.</p>

      <h6 class="fw-bold text-secondary mt-3">Tenant Isolation Principle</h6>
      <p class="small text-muted">All tenant data tables enforce <code>tenant_id</code> foreign key indexing, tenant global scopes at Eloquent level, and mandatory authorization verification in service layer methods and policies.</p>

      <h6 class="fw-bold text-success mt-3">Perishable Vegetable Costing</h6>
      <p class="small text-muted">Inventory movements follow strict FIFO batch accounting to accurately account for purchase rates, freight allocations, crate-to-KG weight conversions, and daily spoilage/wastage deductions.</p>
    </div>
    <x-slot name="footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </x-slot>
  </x-modal>
@endsection
