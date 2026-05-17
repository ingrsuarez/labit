<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->boolean('empty_result_exempt')
                ->default(false)
                ->after('sort_order')
                ->comment('Si está vacía, no bloquea estado del protocolo ni aparece en pendientes');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('empty_result_exempt');
        });
    }
};
