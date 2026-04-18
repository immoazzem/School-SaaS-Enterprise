<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance_records', function (Blueprint $table) {
            $table->time('late_arrival_time')->nullable()->after('status');
            $table->boolean('half_day')->default(false)->after('late_arrival_time');
            $table->string('leave_reference')->nullable()->after('half_day');
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->decimal('min_percent', 5, 2);
            $table->decimal('max_percent', 5, 2);
            $table->decimal('grade_point', 4, 2);
            $table->decimal('fail_below_percent', 5, 2)->nullable();
            $table->string('gpa_calculation_method')->default('simple_average');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'code']);
        });

        Schema::create('marks_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->decimal('marks_obtained', 8, 2)->nullable();
            $table->unsignedSmallInteger('full_marks');
            $table->unsignedSmallInteger('pass_marks');
            $table->boolean('is_absent')->default(false);
            $table->string('absent_reason')->nullable();
            $table->string('verification_status')->default('pending')->index();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('voided')->default(false)->index();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'exam_id', 'class_subject_id', 'student_enrollment_id'], 'marks_unique');
            $table->index(['school_id', 'exam_id']);
        });

        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('billing_type')->default('monthly')->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'code']);
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_group_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('due_day_of_month')->nullable();
            $table->json('months_applicable')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discount_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('discount_type');
            $table->decimal('amount', 12, 2);
            $table->json('applies_to_category_ids')->nullable();
            $table->boolean('is_stackable')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'code']);
        });

        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discount_policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('invoice_no');
            $table->string('fee_month')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->json('discount_breakdown')->nullable();
            $table->decimal('total', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'invoice_no']);
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('payment_method');
            $table->string('transaction_ref')->nullable();
            $table->json('payment_channel_metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->char('month', 7);
            $table->decimal('basic_amount', 12, 2);
            $table->json('allowances')->nullable();
            $table->decimal('gross_amount', 12, 2);
            $table->json('deductions')->nullable();
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'employee_id', 'month']);
        });

        Schema::create('employee_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->index();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'employee_id', 'date']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('max_days_per_year');
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'code']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('total_days');
            $table->unsignedTinyInteger('used_days')->default(0);
            $table->unsignedTinyInteger('remaining_days')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'employee_id', 'leave_type_id', 'academic_year_id'], 'leave_balance_unique');
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedTinyInteger('total_days');
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->timestamp('applied_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employee_attendance_records');
        Schema::dropIfExists('salary_records');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('student_invoices');
        Schema::dropIfExists('student_discounts');
        Schema::dropIfExists('discount_policies');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
        Schema::dropIfExists('marks_entries');
        Schema::dropIfExists('grade_scales');

        Schema::table('student_attendance_records', function (Blueprint $table) {
            $table->dropColumn(['late_arrival_time', 'half_day', 'leave_reference']);
        });
    }
};
