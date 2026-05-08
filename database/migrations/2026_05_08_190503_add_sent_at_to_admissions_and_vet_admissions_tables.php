<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
        });

        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });

        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
