<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para incluir 'fixed_proportional'
        DB::statement("ALTER TABLE salary_items MODIFY COLUMN calculation_type ENUM('percentage', 'fixed', 'fixed_proportional', 'hours') NOT NULL DEFAULT 'percentage'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE salary_items MODIFY COLUMN calculation_type ENUM('percentage', 'fixed', 'hours') NOT NULL DEFAULT 'percentage'");
    }
};
