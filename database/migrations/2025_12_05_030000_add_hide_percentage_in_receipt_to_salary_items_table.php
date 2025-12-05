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
        Schema::table('salary_items', function (Blueprint $table) {
            $table->boolean('hide_percentage_in_receipt')->default(false)->after('requires_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_items', function (Blueprint $table) {
            $table->dropColumn('hide_percentage_in_receipt');
        });
    }
};
