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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 255);
            $table->string('name_ur', 255)->nullable();
            $table->string('code', 50);
            $table->string('barcode', 50)->nullable();
            $table->string('type', 30)->default('vegetable')->index(); // 'vegetable', 'fruit', 'mandi_lot', 'prepack'
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('selling_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('stock_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->decimal('min_selling_price', 12, 2)->default(0.00);
            $table->decimal('reorder_level', 12, 2)->default(10.00);
            $table->boolean('is_perishable')->default(true);
            $table->integer('shelf_life_days')->default(3);
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
