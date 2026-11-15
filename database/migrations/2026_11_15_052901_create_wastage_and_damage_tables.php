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
        Schema::create('wastage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('wastage_no', 50);
            $table->date('date');
            $table->string('reason', 50)->default('spoilage'); // spoilage, drying_shrinkage, handling_damage, pest_damage, trimming_cleaning, expired, other
            $table->string('status', 20)->default('approved'); // pending_approval, approved, rejected
            $table->unsignedInteger('total_items_count')->default(0);
            $table->decimal('total_quantity', 15, 3)->default(0.000);
            $table->decimal('total_loss_cost', 15, 2)->default(0.00);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'wastage_no']);
            $table->index(['tenant_id', 'branch_id', 'date']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('wastage_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wastage_record_id')->constrained('wastage_records')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('unit_code', 20)->default('kg');
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00);
            $table->string('disposal_method', 50)->default('discarded'); // discarded, donated, composted, salvaged
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
        });

        Schema::create('produce_regradings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('regrading_no', 50);
            $table->date('date');
            $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('source_quantity', 15, 3);
            $table->foreignId('target_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('target_quantity', 15, 3);
            $table->decimal('wastage_quantity', 15, 3)->default(0.000); // weight lost or sorted out as rotten
            $table->decimal('source_unit_cost', 15, 2)->default(0.00);
            $table->decimal('target_unit_cost', 15, 2)->default(0.00);
            $table->decimal('loss_cost', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'regrading_no']);
            $table->index(['tenant_id', 'branch_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produce_regradings');
        Schema::dropIfExists('wastage_record_items');
        Schema::dropIfExists('wastage_records');
    }
};
