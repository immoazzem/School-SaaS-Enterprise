<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_marks_obtained', 8, 2);
            $table->decimal('total_full_marks', 8, 2);
            $table->decimal('percentage', 5, 2);
            $table->decimal('gpa', 4, 2);
            $table->string('grade')->nullable();
            $table->unsignedSmallInteger('position_in_class')->nullable();
            $table->boolean('is_pass')->default(false);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['exam_id', 'student_enrollment_id']);
            $table->index(['school_id', 'exam_id']);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('channel')->default('in_app');
            $table->string('subject')->nullable();
            $table->text('body_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'slug', 'channel']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['school_id', 'recipient_user_id', 'read_at']);
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_phone');
            $table->text('message');
            $table->string('status')->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('result_summaries');
    }
};
