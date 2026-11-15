<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. POS Cash Register Sessions (Till Open / Close)
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_no', 50);
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->string('status', 20)->default('open'); // open, closed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        // 2. Sales Invoices Header
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_no', 50);
            $table->date('date');
            $table->string('status', 20)->default('completed'); // completed, cancelled
            $table->string('payment_status', 20)->default('paid'); // paid, partial, unpaid
            $table->string('payment_method', 30)->default('cash'); // cash, credit, bank_card, qr_transfer, split
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->string('discount_type', 20)->default('flat'); // flat, percentage
            $table->decimal('discount_rate', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('rounding_amount', 5, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('change_amount', 15, 2)->default(0.00);
            $table->decimal('cost_amount', 15, 2)->default(0.00); // Total COGS
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_no']);
            $table->index(['tenant_id', 'branch_id', 'date']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'payment_status']);
        });

        // 3. Sales Invoice Line Items
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity', 15, 3); // weighed scale weight or quantity
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'sales_invoice_id']);
        });

        // 4. Split Payments per Invoice
        Schema::create('sales_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('method', 30); // cash, credit, bank_card, qr_transfer
            $table->decimal('amount', 15, 2);
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sales_invoice_id']);
        });

        // 5. Held / Parked Carts
        Schema::create('held_sales_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_number', 50);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('cart_data');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
        });

        // 6. Sales Returns / Refunds
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('return_no', 50);
            $table->date('date');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('refund_method', 30)->default('cash'); // cash, credit_adjustment
            $table->string('reason', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_no']);
            $table->index(['tenant_id', 'sales_invoice_id']);
        });

        // 7. Sales Return Line Items
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'sales_return_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('held_sales_tickets');
        Schema::dropIfExists('sales_invoice_payments');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('pos_sessions');
    }
};
