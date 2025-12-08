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
        Schema::table('samples', function (Blueprint $table) {
            // Estado de validación del protocolo
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])
                ->default('pending')
                ->after('status');
            
            // Usuario que validó
            $table->foreignId('validated_by')
                ->nullable()
                ->after('validation_status')
                ->constrained('users')
                ->onDelete('set null');
            
            // Fecha de validación
            $table->timestamp('validated_at')
                ->nullable()
                ->after('validated_by');
            
            // Notas del validador
            $table->text('validator_notes')
                ->nullable()
                ->after('validated_at');
        });

        Schema::table('sample_determinations', function (Blueprint $table) {
            // Si la determinación individual está validada
            $table->boolean('is_validated')
                ->default(false)
                ->after('observations');
            
            // Usuario que validó esta determinación
            $table->foreignId('validated_by')
                ->nullable()
                ->after('is_validated')
                ->constrained('users')
                ->onDelete('set null');
            
            // Fecha de validación
            $table->timestamp('validated_at')
                ->nullable()
                ->after('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validation_status', 'validated_by', 'validated_at', 'validator_notes']);
        });

        Schema::table('sample_determinations', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['is_validated', 'validated_by', 'validated_at']);
        });
    }
};
