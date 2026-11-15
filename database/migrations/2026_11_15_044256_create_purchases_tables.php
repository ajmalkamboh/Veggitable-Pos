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
        // 1. Purchase Invoices
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('invoice_no', 50);
            $table->string('supplier_invoice_no', 50)->nullable(); // Mandi parchi reference
            $table->date('date')->index();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('received')->index(); // 'draft', 'received', 'cancelled'
            $table->string('payment_status', 30)->default('unpaid')->index(); // 'paid', 'partial', 'unpaid'
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('commission_amount', 15, 2)->default(0.00);
            $table->decimal('mandi_fee', 15, 2)->default(0.00); // Market committee / chungi fee
            $table->decimal('freight_charges', 15, 2)->default(0.00); // Mazdoori & carriage
            $table->decimal('other_charges', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_no']);
            $table->index(['tenant_id', 'supplier_id', 'date']);
            $table->index(['tenant_id', 'branch_id', 'status']);
        });

        // 2. Purchase Invoice Items (Lot lines with gross/tare/net weight)
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity', 12, 3)->default(1.000); // Bags/Crates/Units
            $table->decimal('gross_weight', 12, 3)->nullable(); // Total scale weight
            $table->decimal('tare_weight', 12, 3)->nullable(); // Empty bag/crate weight
            $table->decimal('net_weight', 12, 3)->nullable(); // Net produce weight in Kg
            $table->decimal('unit_price', 12, 2)->default(0.00); // Rate per purchase unit or per kg
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->string('lot_number', 50)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'purchase_invoice_id']);
            $table->index(['tenant_id', 'product_id']);
        });

        // 3. Purchase Returns (Debit Notes)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('return_no', 50);
            $table->date('date')->index();
            $table->string('reason', 255);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('refund_status', 30)->default('adjusted'); // 'adjusted', 'refunded'
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_no']);
            $table->index(['tenant_id', 'supplier_id']);
        });

        // 4. Purchase Return Items
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
    }
};
