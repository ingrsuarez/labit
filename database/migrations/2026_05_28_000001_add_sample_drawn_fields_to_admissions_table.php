<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->timestamp('sample_drawn_at')->nullable()->after('lab_branch_id');
            $table->foreignId('sample_drawn_by')->nullable()->after('sample_drawn_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeign(['sample_drawn_by']);
            $table->dropColumn(['sample_drawn_at', 'sample_drawn_by']);
        });
    }
};
