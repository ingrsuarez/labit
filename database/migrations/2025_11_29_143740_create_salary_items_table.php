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
        Schema::create('salary_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('haber');
            $table->string('calculation_type')->default('percentage');
            $table->string('calculation_base')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->string('base')->default('basic_salary');
            $table->boolean('is_remunerative')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_assignment')->default(false);
            $table->boolean('hide_percentage_in_receipt')->default(false);
            $table->boolean('includes_in_antiguedad_base')->default(false);
            $table->boolean('applies_all_year')->default(true);
            $table->integer('recurrent_month')->nullable();
            $table->integer('specific_month')->nullable();
            $table->integer('specific_year')->nullable();
            $table->integer('order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_items');
    }
};
