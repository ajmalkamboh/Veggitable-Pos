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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('expense_no', 50);
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)->default('cash'); // cash, bank, online, cheque
            $table->string('payee_name')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('approved'); // approved, pending_approval, rejected
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency', 20)->nullable(); // daily, weekly, monthly
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'expense_no']);
            $table->index(['tenant_id', 'branch_id', 'date']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
