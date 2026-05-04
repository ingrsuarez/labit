<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vet_admission_tests MODIFY status ENUM('pending','in_progress','completed','validated') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE vet_admission_tests SET status = 'completed' WHERE status = 'validated'");
            DB::statement("ALTER TABLE vet_admission_tests MODIFY status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'");
        }
    }
};
