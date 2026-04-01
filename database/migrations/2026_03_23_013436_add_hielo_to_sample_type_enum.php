<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('samples', function (Blueprint $table) {
                $table->string('sample_type_new')->default('agua')->after('protocol_number');
            });
            DB::table('samples')->update(['sample_type_new' => DB::raw('sample_type')]);
            Schema::table('samples', function (Blueprint $table) {
                $table->dropColumn('sample_type');
            });
            Schema::table('samples', function (Blueprint $table) {
                $table->renameColumn('sample_type_new', 'sample_type');
            });
        } else {
            DB::statement("ALTER TABLE samples MODIFY COLUMN sample_type ENUM('agua', 'alimento', 'hielo') DEFAULT 'agua'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE samples MODIFY COLUMN sample_type ENUM('agua', 'alimento') DEFAULT 'agua'");
    }
};
