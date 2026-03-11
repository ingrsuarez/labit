<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sample_determinations', function (Blueprint $table) {
            $table->foreignId('reference_category_id')
                ->nullable()
                ->after('reference_value')
                ->constrained('reference_categories')
                ->nullOnDelete();
        });

        // Poblar reference_category_id para determinaciones existentes
        // Solo cuando hay UNA coincidencia exacta (sin ambigüedad).
        // Si el mismo valor existe en múltiples categorías, se deja null
        // y el PDF usará el fallback de búsqueda por valor.
        $determinations = \App\Models\SampleDetermination::whereNotNull('reference_value')
            ->whereNull('reference_category_id')
            ->get();

        foreach ($determinations as $det) {
            $matches = \App\Models\TestReferenceValue::where('test_id', $det->test_id)
                ->where('value', $det->reference_value)
                ->whereNotNull('reference_category_id')
                ->get();

            if ($matches->count() === 1) {
                $det->update(['reference_category_id' => $matches->first()->reference_category_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sample_determinations', function (Blueprint $table) {
            $table->dropForeign(['reference_category_id']);
            $table->dropColumn('reference_category_id');
        });
    }
};
