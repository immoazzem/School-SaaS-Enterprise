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
        Schema::create('promotion_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('to_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('from_academic_class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('to_academic_class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('processed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('promotion_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('action')->default('promoted')->index();
            $table->foreignId('new_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['promotion_batch_id', 'student_enrollment_id'], 'promo_record_enrollment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_records');
        Schema::dropIfExists('promotion_batches');
    }
};
