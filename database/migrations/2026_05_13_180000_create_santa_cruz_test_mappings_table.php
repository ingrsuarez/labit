<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santa_cruz_test_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('prestacion_code')->unique();
            $table->string('prestacion_name')->nullable();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santa_cruz_test_mappings');
    }
};
