<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivot: una equivalencia A25 puede mapear a múltiples determinaciones Labit
        Schema::create('a25_analyte_mapping_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('a25_analyte_mapping_id')
                ->constrained('a25_analyte_mappings')
                ->cascadeOnDelete();
            $table->foreignId('test_id')
                ->constrained('tests')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['a25_analyte_mapping_id', 'test_id'], 'a25_mapping_test_unique');
        });

        // Migrar datos existentes: copiar test_id actual a la nueva tabla pivot
        DB::table('a25_analyte_mappings')
            ->whereNotNull('test_id')
            ->get()
            ->each(function ($mapping) {
                DB::table('a25_analyte_mapping_tests')->insert([
                    'a25_analyte_mapping_id' => $mapping->id,
                    'test_id'                => $mapping->test_id,
                    'sort_order'             => 0,
                ]);
            });

        // Quitar la columna test_id (ya no es necesaria; los tests van en el pivot)
        Schema::table('a25_analyte_mappings', function (Blueprint $table) {
            $table->dropForeign(['test_id']);
            $table->dropColumn('test_id');
        });
    }

    public function down(): void
    {
        // Restaurar columna test_id
        Schema::table('a25_analyte_mappings', function (Blueprint $table) {
            $table->foreignId('test_id')->nullable()->constrained('tests')->cascadeOnDelete();
        });

        // Restaurar primer test de cada mapping
        DB::table('a25_analyte_mapping_tests')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('a25_analyte_mapping_id')
            ->each(function ($rows, $mappingId) {
                $first = $rows->first();
                DB::table('a25_analyte_mappings')
                    ->where('id', $mappingId)
                    ->update(['test_id' => $first->test_id]);
            });

        Schema::dropIfExists('a25_analyte_mapping_tests');
    }
};
