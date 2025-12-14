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
        Schema::create('non_conformity_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('non_conformity_id');
            $table->unsignedBigInteger('user_id')->comment('Usuario que hace el seguimiento');
            $table->text('notes')->comment('Notas del seguimiento');
            $table->string('status_change')->nullable()->comment('Cambio de estado si hubo');
            $table->timestamps();

            $table->foreign('non_conformity_id')->references('id')->on('non_conformities')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('non_conformity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_conformity_follow_ups');
    }
};
