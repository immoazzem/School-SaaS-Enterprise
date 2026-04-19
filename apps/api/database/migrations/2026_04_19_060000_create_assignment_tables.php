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
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->string('status', 40)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'academic_class_id', 'subject_id'], 'assignment_class_subject_lookup');
            $table->index(['school_id', 'due_date', 'status'], 'assignment_due_lookup');
        });

        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->decimal('marks_awarded', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('status', 40)->default('submitted')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assignment_id', 'student_enrollment_id'], 'assignment_submission_unique');
            $table->index(['school_id', 'status'], 'assignment_submission_status_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
