<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_admission_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vet_admission_id')->constrained('vet_admissions')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('tests')->onDelete('restrict');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('nbu_units', 10, 2)->default(1);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->string('result')->nullable();
            $table->string('unit')->nullable();
            $table->string('reference_value')->nullable();
            $table->string('method')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('analyzed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('analyzed_at')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_admission_tests');
    }
};
