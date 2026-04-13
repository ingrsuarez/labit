<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('veterinary_nbu_value', 12, 2)
                ->nullable()
                ->after('discount_percent')
                ->comment('Valor en $ de 1 NBU para clientes tipo veterinario (precio práctica = NBU práctica × este valor)');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('veterinary_nbu_value');
        });
    }
};
