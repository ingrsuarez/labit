<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE samples MODIFY COLUMN validation_status ENUM('pending', 'partial', 'validated', 'rejected') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE samples MODIFY COLUMN validation_status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending'");
        }
    }
};



