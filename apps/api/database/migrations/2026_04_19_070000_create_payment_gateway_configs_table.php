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
        Schema::create('payment_gateway_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 40)->index();
            $table->text('credentials_encrypted');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('test_mode')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'gateway'], 'payment_gateway_school_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};
