<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->string('owner_email')->nullable()->after('owner_phone');
        });
    }

    public function down(): void
    {
        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->dropColumn('owner_email');
        });
    }
};
