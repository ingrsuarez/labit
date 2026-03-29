<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->json('type')->nullable()->after('status');
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM customers'))->pluck('Key_name')->unique()->toArray();

        if (in_array('customers_taxid_unique', $indexes)) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['taxId']);
            });
        }

        if (in_array('customers_email_unique', $indexes)) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }

        DB::table('customers')->whereNull('type')->update(['type' => json_encode(['aguas'])]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
