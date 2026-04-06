<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NBU en prácticas (tests) debe admitir decimales (p. ej. 1,5).
     * La columna original era integer y truncaba/redondeaba al persistir.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->decimal('nbu', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->integer('nbu')->nullable()->change();
        });
    }
};
