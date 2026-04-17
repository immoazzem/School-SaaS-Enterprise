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
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('roll_no', 40)->nullable();
            $table->date('enrolled_on');
            $table->string('status', 40)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'student_id', 'academic_year_id'], 'student_enrollment_year_unique');
            $table->unique([
                'school_id',
                'academic_year_id',
                'academic_class_id',
                'academic_section_id',
                'roll_no',
            ], 'student_enrollment_roll_unique');
            $table->index(['school_id', 'academic_year_id', 'academic_class_id', 'status'], 'student_enrollment_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
