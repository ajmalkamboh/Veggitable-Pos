<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubscriptionService
{
    /**
     * Assign a plan to a tenant and create the subscription record.
     */
    public function assignPlan(
        Tenant $tenant,
        SubscriptionPlan $plan,
        BillingCycle $cycle = BillingCycle::Monthly,
        float $amountPaid = 0.0,
        string $paymentMethod = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): Subscription {
        return DB::transaction(function () use ($tenant, $plan, $cycle, $amountPaid, $paymentMethod, $reference, $notes) {
            $now = now();
            $isPaid = $amountPaid > 0;

            // Determine start and end timestamps
            if ($isPaid) {
                $status = SubscriptionStatus::Active;
                $startsAt = $now;
                $endsAt = $cycle === BillingCycle::Yearly ? $now->copy()->addYear() : $now->copy()->addMonth();
                $graceEndsAt = $endsAt->copy()->addDays($plan->grace_days);
                $trialStartsAt = null;
                $trialEndsAt = null;
            } else {
                $status = SubscriptionStatus::Trialing;
                $startsAt = $now;
                $trialStartsAt = $now;
                $trialEndsAt = $now->copy()->addDays($plan->trial_days);
                $endsAt = $trialEndsAt;
                $graceEndsAt = $endsAt->copy()->addDays($plan->grace_days);
            }

            // Create subscription record
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'status' => $status,
                'trial_starts_at' => $trialStartsAt,
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_ends_at' => $graceEndsAt,
                'price_paid' => $amountPaid,
                'currency' => $tenant->currency,
            ]);

            // Update tenant's active plan and subscription pointers
            $tenant->update([
                'plan_id' => $plan->id,
                'current_subscription_id' => $subscription->id,
                'trial_ends_at' => $trialEndsAt,
                'subscription_ends_at' => $endsAt,
                'status' => TenantStatus::Active,
            ]);

            // If a payment was made, generate invoice
            if ($isPaid) {
                $this->createInvoice(
                    tenant: $tenant,
                    subscription: $subscription,
                    plan: $plan,
                    amount: $amountPaid,
                    paymentMethod: $paymentMethod,
                    reference: $reference,
                    notes: $notes ?? "Plan subscription: {$plan->name} ({$cycle->label()})"
                );
            }

            return $subscription;
        });
    }

    /**
     * Renew an existing tenant subscription with manual payment.
     */
    public function renewSubscription(
        Tenant $tenant,
        int $durationMonths,
        float $amountPaid,
        string $paymentMethod = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): SubscriptionInvoice {
        return DB::transaction(function () use ($tenant, $durationMonths, $amountPaid, $paymentMethod, $reference, $notes) {
            $subscription = $tenant->currentSubscription;
            $plan = $tenant->plan;

            $baseDate = ($subscription && $subscription->ends_at && $subscription->ends_at->isFuture())
                ? $subscription->ends_at
                : now();

            $newEndsAt = $baseDate->copy()->addMonths($durationMonths);
            $graceDays = $plan?->grace_days ?? 3;
            $newGraceEndsAt = $newEndsAt->copy()->addDays($graceDays);

            if ($subscription) {
                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                    'ends_at' => $newEndsAt,
                    'grace_ends_at' => $newGraceEndsAt,
                    'price_paid' => $subscription->price_paid + $amountPaid,
                ]);
            }

            $tenant->update([
                'status' => TenantStatus::Active,
                'subscription_ends_at' => $newEndsAt,
            ]);

            return $this->createInvoice(
                tenant: $tenant,
                subscription: $subscription,
                plan: $plan,
                amount: $amountPaid,
                paymentMethod: $paymentMethod,
                reference: $reference,
                notes: $notes ?? "Subscription renewal for {$durationMonths} months."
            );
        });
    }

    /**
     * Record a manual payment and invoice for a tenant.
     */
    public function recordManualPayment(
        Tenant $tenant,
        float $amount,
        string $paymentMethod = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): SubscriptionInvoice {
        return $this->createInvoice(
            tenant: $tenant,
            subscription: $tenant->currentSubscription,
            plan: $tenant->plan,
            amount: $amount,
            paymentMethod: $paymentMethod,
            reference: $reference,
            notes: $notes
        );
    }

    /**
     * Create an invoice record.
     */
    protected function createInvoice(
        Tenant $tenant,
        ?Subscription $subscription,
        ?SubscriptionPlan $plan,
        float $amount,
        string $paymentMethod = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): SubscriptionInvoice {
        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription?->id,
            'plan_id' => $plan?->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $amount,
            'currency' => $tenant->currency,
            'payment_method' => $paymentMethod,
            'payment_status' => InvoiceStatus::Paid,
            'transaction_reference' => $reference,
            'paid_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Generate unique sequential invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(Str::random(4));
        $count = SubscriptionInvoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%s-%04d-%s', $year, $count, $random);
    }

    /**
     * Assert that a tenant has entitlement to a feature, or abort with 403.
     */
    public function assertHasFeature(Tenant $tenant, string $featureKey): void
    {
        if (! $tenant->hasFeature($featureKey)) {
            $featureName = SubscriptionPlan::availableFeatures()[$featureKey] ?? $featureKey;
            throw new HttpException(403, "Feature upgrade required: '{$featureName}' is not included in your current subscription plan.");
        }
    }

    /**
     * Assert that a tenant can add another branch.
     */
    public function assertCanAddBranch(Tenant $tenant): void
    {
        if (! $tenant->canAddBranch()) {
            $max = $tenant->plan?->max_branches ?? 1;
            throw new HttpException(403, "Branch limit reached: Your subscription plan allows a maximum of {$max} branch(es). Please upgrade your plan.");
        }
    }

    /**
     * Assert that a tenant can add another user.
     */
    public function assertCanAddUser(Tenant $tenant): void
    {
        if (! $tenant->canAddUser()) {
            $max = $tenant->plan?->max_users ?? 2;
            throw new HttpException(403, "User account limit reached: Your subscription plan allows a maximum of {$max} user(s). Please upgrade your plan.");
        }
    }
}
