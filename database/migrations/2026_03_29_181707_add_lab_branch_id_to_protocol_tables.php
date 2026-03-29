<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('created_by')
                  ->constrained('lab_branches')->nullOnDelete();
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('created_by')
                  ->constrained('lab_branches')->nullOnDelete();
        });

        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('created_by')
                  ->constrained('lab_branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });

        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });
    }
};
