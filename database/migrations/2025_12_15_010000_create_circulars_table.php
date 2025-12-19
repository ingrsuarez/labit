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
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código único CIR-YYYY-XXX');
            $table->string('title')->comment('Título de la circular');
            $table->unsignedBigInteger('created_by')->comment('Usuario que crea la circular');
            $table->date('date')->comment('Fecha de la circular');
            $table->enum('status', ['activa', 'inactiva'])->default('activa');
            $table->string('sector')->comment('Sector/Área destinataria');
            $table->text('description')->comment('Contenido de la circular');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('status');
            $table->index('sector');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circulars');
    }
};
