<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_species_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');
            $table->foreignId('species_id')->constrained('species')->onDelete('cascade');
            $table->string('low')->nullable();
            $table->string('high')->nullable();
            $table->text('other_reference')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'species_id'], 'test_species_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_species_references');
    }
};
