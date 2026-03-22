<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `test_reference_values` MODIFY `reference_category_id` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        //
    }
};



