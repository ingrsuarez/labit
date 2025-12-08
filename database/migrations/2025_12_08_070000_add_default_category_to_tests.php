<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campo para categoría de referencia predeterminada en tests padre
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedBigInteger('default_reference_category_id')->nullable()->after('parent');
            $table->foreign('default_reference_category_id')
                  ->references('id')
                  ->on('reference_categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['default_reference_category_id']);
            $table->dropColumn('default_reference_category_id');
        });
    }
};
