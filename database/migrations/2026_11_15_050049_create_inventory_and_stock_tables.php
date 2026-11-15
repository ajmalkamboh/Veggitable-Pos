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
        // 1. Real-time Aggregate Stock per Product per Branch
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0.000); // in stock unit (e.g. kg)
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('reorder_level', 15, 3)->default(0.000);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'product_id'], 'inv_stock_tenant_branch_prod_unique');
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'product_id']);
        });

        // 2. Produce Batches / Lots (FIFO Traceability)
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->foreignId('purchase_invoice_item_id')->nullable()->constrained('purchase_invoice_items')->nullOnDelete();
            $table->string('lot_number', 50);
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('initial_quantity', 15, 3);
            $table->decimal('current_quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->string('status', 20)->default('active'); // active, depleted, expired, damaged
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'branch_id', 'product_id', 'status'], 'inv_batch_lookup_idx');
            $table->index(['tenant_id', 'lot_number']);
        });

        // 3. Immutable Stock Movement Audit Ledger
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('type', 30); // purchase_in, sale_out, transfer_out, transfer_in, adjustment_in, adjustment_out, wastage_out
            $table->string('reference_type', 50)->nullable(); // purchase_invoice, pos_sale, stock_transfer, stock_adjustment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->decimal('quantity_change', 15, 3);
            $table->decimal('quantity_before', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'product_id', 'created_at'], 'stock_movements_lookup_idx');
            $table->index(['tenant_id', 'type']);
        });

        // 4. Inter-Branch Stock Transfers
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('transfer_no', 50);
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->string('status', 20)->default('pending'); // pending, in_transit, received, cancelled
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'transfer_no']);
            $table->index(['tenant_id', 'from_branch_id', 'to_branch_id', 'status'], 'stock_transfer_lookup_idx');
        });

        // 5. Stock Transfer Line Items
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity_dispatched', 15, 3);
            $table->decimal('quantity_received', 15, 3)->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['tenant_id', 'stock_transfer_id']);
        });

        // 6. Stock Physical Count & Discrepancy Adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('adjustment_no', 50);
            $table->date('date');
            $table->string('reason', 50); // physical_count_discrepancy, drying_weight_loss, damage_spoilage, excess_produce_found, other
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'adjustment_no']);
            $table->index(['tenant_id', 'branch_id', 'date']);
        });

        // 7. Stock Adjustment Line Items
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('current_quantity', 15, 3);
            $table->decimal('counted_quantity', 15, 3);
            $table->decimal('difference_quantity', 15, 3); // counted - current
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['tenant_id', 'stock_adjustment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_stocks');
    }
};
