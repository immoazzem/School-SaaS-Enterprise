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
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('plan')->default('starter')->after('status')->index();
            $table->string('subscription_status')->default('trialing')->after('plan')->index();
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->json('plan_limits')->nullable()->after('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn([
                'plan',
                'subscription_status',
                'trial_ends_at',
                'plan_limits',
            ]);
        });
    }
};
