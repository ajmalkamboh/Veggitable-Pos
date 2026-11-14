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
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false)->index()->after('settings');
            $table->integer('onboarding_step')->default(1)->after('onboarding_completed');
            $table->string('logo_path', 255)->nullable()->after('onboarding_step');
            $table->string('financial_year_start', 10)->default('07-01')->after('logo_path');
            $table->date('business_opening_date')->nullable()->after('financial_year_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_completed',
                'onboarding_step',
                'logo_path',
                'financial_year_start',
                'business_opening_date',
            ]);
        });
    }
};
