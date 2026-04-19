<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('name_bn', 160)->nullable()->after('full_name');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->string('name_bn', 160)->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('name_bn');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('name_bn');
        });
    }
};
