<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.76.0 — Indicador "Ratificado" por determinación.
 * Marca opcional para resultados anormales/atípicos verificados por el equipo
 * antes de cerrar la validación (clínico, vet y muestras aguas/alimentos).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $supportsFk = $driver !== 'sqlite';

        Schema::table('admission_tests', function (Blueprint $table) use ($supportsFk) {
            $table->boolean('is_ratified')->default(false)->after('validated_at');
            $table->timestamp('ratified_at')->nullable()->after('is_ratified');
            $table->unsignedBigInteger('ratified_by')->nullable()->after('ratified_at');

            if ($supportsFk) {
                $table->foreign('ratified_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        Schema::table('vet_admission_tests', function (Blueprint $table) use ($supportsFk) {
            $table->boolean('is_ratified')->default(false)->after('validated_at');
            $table->timestamp('ratified_at')->nullable()->after('is_ratified');
            $table->unsignedBigInteger('ratified_by')->nullable()->after('ratified_at');

            if ($supportsFk) {
                $table->foreign('ratified_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        Schema::table('sample_determinations', function (Blueprint $table) use ($supportsFk) {
            $table->boolean('is_ratified')->default(false)->after('validated_at');
            $table->timestamp('ratified_at')->nullable()->after('is_ratified');
            $table->unsignedBigInteger('ratified_by')->nullable()->after('ratified_at');

            if ($supportsFk) {
                $table->foreign('ratified_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $supportsFk = $driver !== 'sqlite';

        Schema::table('sample_determinations', function (Blueprint $table) use ($supportsFk) {
            if ($supportsFk) {
                $table->dropForeign(['ratified_by']);
            }
            $table->dropColumn(['is_ratified', 'ratified_at', 'ratified_by']);
        });

        Schema::table('vet_admission_tests', function (Blueprint $table) use ($supportsFk) {
            if ($supportsFk) {
                $table->dropForeign(['ratified_by']);
            }
            $table->dropColumn(['is_ratified', 'ratified_at', 'ratified_by']);
        });

        Schema::table('admission_tests', function (Blueprint $table) use ($supportsFk) {
            if ($supportsFk) {
                $table->dropForeign(['ratified_by']);
            }
            $table->dropColumn(['is_ratified', 'ratified_at', 'ratified_by']);
        });
    }
};
