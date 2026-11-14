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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('current_branch_id')->nullable()->after('tenant_id')->constrained('branches')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->boolean('is_tenant_owner')->default(false)->after('is_super_admin');
            $table->string('status', 30)->default('active')->after('is_tenant_owner');
            $table->string('phone', 50)->nullable()->after('email');
            $table->json('preferences')->nullable()->after('status');

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['current_branch_id']);
            $table->dropColumn([
                'tenant_id',
                'current_branch_id',
                'is_super_admin',
                'is_tenant_owner',
                'status',
                'phone',
                'preferences',
            ]);
        });
    }
};
