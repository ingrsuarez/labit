<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega 'partial' al enum validation_status
     */
    public function up(): void
    {
        // Cambiar el ENUM para incluir 'partial'
        DB::statement("ALTER TABLE samples MODIFY COLUMN validation_status ENUM('pending', 'partial', 'validated', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM original
        DB::statement("ALTER TABLE samples MODIFY COLUMN validation_status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending'");
    }
};



