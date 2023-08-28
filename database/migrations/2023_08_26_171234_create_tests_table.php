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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('low')->nullable();
            $table->string('high')->nullable();
            $table->string('instructions')->nullable();
            $table->unsignedBigInteger('parent')->nullable();
            $table->integer('decimals')->nullable();
            $table->string('negative')->nullable();
            $table->string('positive')->nullable();
            $table->string('questions')->nullable();
            $table->string('code');
            $table->string('method')->nullable();
            $table->float('price')->nullable();
            $table->float('cost')->nullable();
            $table->string('work_sheet')->nullable();
            $table->unsignedBigInteger('material')->nullable();
            $table->string('formula')->nullable();
            $table->unsignedBigInteger('box')->nullable();
            $table->integer('nbu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
