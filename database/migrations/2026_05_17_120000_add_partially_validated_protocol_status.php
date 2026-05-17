<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE vet_admissions MODIFY status ENUM('pending','in_progress','completed','partially_validated','validated','cancelled') NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE samples MODIFY status ENUM('pending','in_progress','completed','partially_validated','validated','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('vet_admissions')->where('status', 'partially_validated')->update(['status' => 'completed']);
        DB::table('samples')->where('status', 'partially_validated')->update(['status' => 'completed']);
        DB::table('samples')->where('status', 'validated')->update(['status' => 'completed']);

        DB::statement("ALTER TABLE vet_admissions MODIFY status ENUM('pending','in_progress','completed','validated','cancelled') NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE samples MODIFY status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
