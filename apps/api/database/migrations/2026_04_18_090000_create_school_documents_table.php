<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->index();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('mime_type', 120);
            $table->boolean('is_public')->default(false)->index();
            $table->nullableMorphs('related_model');
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'category']);
            $table->index(['school_id', 'uploader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_documents');
    }
};
