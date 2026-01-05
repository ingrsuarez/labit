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
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('protocol_number')->unique(); // Número de protocolo autogenerado
            $table->enum('sample_type', ['agua', 'alimento'])->default('agua');
            $table->date('entry_date'); // Fecha de ingreso
            $table->date('sampling_date'); // Fecha de toma de muestra
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('location'); // Lugar de toma
            $table->string('address')->nullable(); // Dirección
            $table->string('batch')->nullable(); // Lote (para alimentos)
            $table->string('product_name')->nullable(); // Nombre del producto (para alimentos)
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};











