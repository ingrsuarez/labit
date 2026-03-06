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
        Schema::create('salary_item_base_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_item_id')->constrained('salary_items')->onDelete('cascade');
            $table->string('base_item_key');
            $table->timestamps();

            $table->unique(['salary_item_id', 'base_item_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_item_base_items');
    }
};
