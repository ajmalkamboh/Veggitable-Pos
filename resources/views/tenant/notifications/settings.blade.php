@extends('layouts.admin')

@section('title', __('Notification Channels & Automation Rules'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Notification & Automation Settings') }}</h4>
    <p class="text-muted small mb-0">{{ __('Configure real-time alert triggers, email digests, SMS gateways, and WhatsApp notifications') }}</p>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="{{ route('tenant.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-bell me-1"></i> {{ __('View Alerts Center') }}
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0 text-dark">
      <i class="bi bi-gear-wide-connected me-2 text-primary"></i>{{ __('Notification Channels & Dispatch Rules') }}
    </h6>
  </div>
  <div class="card-body p-4">
    <form method="POST" action="{{ route('tenant.notifications.settings.update') }}">
      @csrf

      <!-- Automated Event Triggers -->
      <h6 class="fw-bold text-primary small text-uppercase mb-3">
        <i class="bi bi-lightning-charge me-1"></i> {{ __('1. Automated Business Triggers') }}
      </h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="form-check form-switch p-3 border rounded bg-light">
            <input class="form-check-input ms-0 me-2" type="checkbox" name="notify_low_stock" value="1" id="lowStockCheck"
              {{ !empty($settings['notify_low_stock']) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="lowStockCheck">
              {{ __('Low Stock Alerts') }}
              <div class="text-muted small fw-normal">{{ __('Alert managers when produce quantity reaches or falls below reorder level.') }}</div>
            </label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-check form-switch p-3 border rounded bg-light">
            <input class="form-check-input ms-0 me-2" type="checkbox" name="notify_daily_summary" value="1" id="dailySummaryCheck"
              {{ !empty($settings['notify_daily_summary']) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="dailySummaryCheck">
              {{ __('Daily Sales & Wastage Summaries') }}
              <div class="text-muted small fw-normal">{{ __('Dispatch closing turnover, cash collections, and cull loss summaries at day end.') }}</div>
            </label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-check form-switch p-3 border rounded bg-light">
            <input class="form-check-input ms-0 me-2" type="checkbox" name="notify_customer_overdue" value="1" id="customerOverdueCheck"
              {{ !empty($settings['notify_customer_overdue']) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="customerOverdueCheck">
              {{ __('Customer Overdue Credit Alerts') }}
              <div class="text-muted small fw-normal">{{ __('Notify accountants when customer credit accounts exceed permitted thresholds.') }}</div>
            </label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-check form-switch p-3 border rounded bg-light">
            <input class="form-check-input ms-0 me-2" type="checkbox" name="notify_supplier_due" value="1" id="supplierDueCheck"
              {{ !empty($settings['notify_supplier_due']) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="supplierDueCheck">
              {{ __('Mandi Supplier Payment Reminders') }}
              <div class="text-muted small fw-normal">{{ __('Alert purchasers regarding due commission agent lot bills.') }}</div>
            </label>
          </div>
        </div>
      </div>

      <!-- Delivery Channels -->
      <h6 class="fw-bold text-success small text-uppercase mb-3 pt-3 border-top">
        <i class="bi bi-broadcast me-1"></i> {{ __('2. Delivery Channels') }}
      </h6>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="email_channel" value="1" id="emailChannelCheck"
                {{ !empty($settings['email_channel']) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="emailChannelCheck">
                <i class="bi bi-envelope text-primary me-1"></i> {{ __('Email Notifications') }}
              </label>
            </div>
            <p class="text-muted small mt-2 mb-0">{{ __('Sends email digests to registered store owners and managers.') }}</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="sms_channel" value="1" id="smsChannelCheck"
                {{ !empty($settings['sms_channel']) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="smsChannelCheck">
                <i class="bi bi-chat-dots text-success me-1"></i> {{ __('SMS Gateway') }}
              </label>
            </div>
            <p class="text-muted small mt-2 mb-0">{{ __('Sends automated SMS alerts to customer and supplier phone numbers.') }}</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="whatsapp_channel" value="1" id="whatsappChannelCheck"
                {{ !empty($settings['whatsapp_channel']) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="whatsappChannelCheck">
                <i class="bi bi-whatsapp text-success me-1"></i> {{ __('WhatsApp Business API') }}
              </label>
            </div>
            <p class="text-muted small mt-2 mb-0">{{ __('Webhook integration for WhatsApp transaction slips and payment links.') }}</p>
          </div>
        </div>
      </div>

      <!-- Gateway Parameters (Zero hardcoding) -->
      <h6 class="fw-bold text-secondary small text-uppercase mb-3 pt-3 border-top">
        <i class="bi bi-sliders2 me-1"></i> {{ __('3. SMS & WhatsApp Gateway Configuration (Optional)') }}
      </h6>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">{{ __('SMS Provider Gateway') }}</label>
          <input type="text" name="sms_provider" class="form-control form-control-sm"
            value="{{ $settings['sms_provider'] ?? 'generic_gateway' }}" placeholder="e.g. Twilio, Jazz, SMS4Connect">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">{{ __('SMS Brand / Masking Sender ID') }}</label>
          <input type="text" name="sms_sender_id" class="form-control form-control-sm"
            value="{{ $settings['sms_sender_id'] ?? 'VEGGITABLE' }}" placeholder="e.g. VEGGITABLE">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">{{ __('SMS Gateway HTTP Webhook Endpoint') }}</label>
          <input type="url" name="sms_api_url" class="form-control form-control-sm"
            value="{{ $settings['sms_api_url'] ?? '' }}" placeholder="https://api.sms-gateway.com/send">
        </div>
        <div class="col-md-12">
          <label class="form-label small fw-semibold">{{ __('WhatsApp Cloud Webhook URL') }}</label>
          <input type="url" name="whatsapp_webhook_url" class="form-control form-control-sm"
            value="{{ $settings['whatsapp_webhook_url'] ?? '' }}" placeholder="https://graph.facebook.com/v18.0/phone-number-id/messages">
          <small class="text-muted">{{ __('Third-party credentials are securely kept in tenant settings and never exposed publicly.') }}</small>
        </div>
      </div>

      <div class="text-end pt-3 border-top">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-save me-1"></i> {{ __('Save Notification Configuration') }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
