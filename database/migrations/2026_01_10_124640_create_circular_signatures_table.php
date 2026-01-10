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
        Schema::create('circular_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->nullable(); // Cuando leyó la circular
            $table->timestamp('signed_at')->nullable(); // Cuando firmó la circular
            $table->string('ip_address')->nullable(); // IP desde donde firmó
            $table->string('user_agent')->nullable(); // Navegador/dispositivo
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['circular_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circular_signatures');
    }
};
