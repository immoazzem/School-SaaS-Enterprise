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
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_no', 40);
            $table->string('full_name', 160);
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('gender', 40)->nullable();
            $table->string('religion', 80)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joined_on');
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('employee_type', 40)->default('staff');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'employee_no']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'designation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
