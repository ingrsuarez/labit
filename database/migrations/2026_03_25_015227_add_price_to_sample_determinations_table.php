<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_determinations', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('test_id');
        });
    }

    public function down(): void
    {
        Schema::table('sample_determinations', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
