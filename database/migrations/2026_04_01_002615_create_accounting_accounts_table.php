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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->index();
            $table->string('name', 255);
            $table->enum('type', ['activo', 'pasivo', 'patrimonio_neto', 'resultado_positivo', 'resultado_negativo']);
            $table->foreignId('parent_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->tinyInteger('level');
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
