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
        // Tabla de categorías/normativas de referencia
        Schema::create('reference_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Código Alimentario Argentino", "Agua Envasada", "Recursos Hídricos"
            $table->string('code')->unique(); // Ej: "CAA", "AE", "RH"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabla de valores de referencia por test y categoría
        Schema::create('test_reference_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');
            $table->foreignId('reference_category_id')->constrained('reference_categories')->onDelete('cascade');
            $table->string('value'); // El valor de referencia (ej: "< 500 UFC/ml", "Ausente", "0 - 10")
            $table->string('min_value')->nullable(); // Valor mínimo numérico (para comparaciones)
            $table->string('max_value')->nullable(); // Valor máximo numérico (para comparaciones)
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // Si es el valor por defecto para este test
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['test_id', 'reference_category_id'], 'test_ref_category_unique');
        });

        // Insertar categorías comunes
        DB::table('reference_categories')->insert([
            [
                'name' => 'Código Alimentario Argentino',
                'code' => 'CAA',
                'description' => 'Valores según el Código Alimentario Argentino',
                'is_active' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agua Envasada',
                'code' => 'AE',
                'description' => 'Valores de referencia para agua envasada',
                'is_active' => true,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Recursos Hídricos',
                'code' => 'RH',
                'description' => 'Valores según normativa de recursos hídricos',
                'is_active' => true,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agua de Red',
                'code' => 'AR',
                'description' => 'Valores para agua de red/potable',
                'is_active' => true,
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agua de Pozo',
                'code' => 'AP',
                'description' => 'Valores para agua de pozo',
                'is_active' => true,
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_reference_values');
        Schema::dropIfExists('reference_categories');
    }
};
