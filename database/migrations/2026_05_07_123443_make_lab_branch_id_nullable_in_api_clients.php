<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Antes de revertir, cualquier registro con lab_branch_id = null fallaría.
        // Solo revertir si todos los registros tienen lab_branch_id asignado.
        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable(false)->change();
        });
    }
};
