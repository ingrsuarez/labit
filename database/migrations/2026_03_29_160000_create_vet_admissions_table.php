<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('protocol_number')->unique();
            $table->date('date');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('veterinarian_id')->nullable()->constrained('veterinarians')->onDelete('set null');
            $table->foreignId('species_id')->constrained('species')->onDelete('restrict');
            $table->string('animal_name');
            $table->string('owner_name');
            $table->string('owner_phone')->nullable();
            $table->string('breed')->nullable();
            $table->string('age')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('observations')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_admissions');
    }
};
