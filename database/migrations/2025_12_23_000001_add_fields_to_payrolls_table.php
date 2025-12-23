<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Solo agregar columnas si no existen
            if (!Schema::hasColumn('payrolls', 'employee_id')) {
                $table->foreignId('employee_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('payrolls', 'year')) {
                $table->integer('year')->after('employee_id');
            }
            if (!Schema::hasColumn('payrolls', 'month')) {
                $table->integer('month')->after('year');
            }
            if (!Schema::hasColumn('payrolls', 'period_label')) {
                $table->string('period_label')->nullable()->after('month');
            }
            if (!Schema::hasColumn('payrolls', 'employee_name')) {
                $table->string('employee_name')->after('period_label');
            }
            if (!Schema::hasColumn('payrolls', 'employee_cuil')) {
                $table->string('employee_cuil')->nullable()->after('employee_name');
            }
            if (!Schema::hasColumn('payrolls', 'category_name')) {
                $table->string('category_name')->nullable()->after('employee_cuil');
            }
            if (!Schema::hasColumn('payrolls', 'position_name')) {
                $table->string('position_name')->nullable()->after('category_name');
            }
            if (!Schema::hasColumn('payrolls', 'antiguedad_years')) {
                $table->integer('antiguedad_years')->default(0)->after('position_name');
            }
            if (!Schema::hasColumn('payrolls', 'start_date')) {
                $table->date('start_date')->nullable()->after('antiguedad_years');
            }
            if (!Schema::hasColumn('payrolls', 'salario_basico')) {
                $table->decimal('salario_basico', 12, 2)->default(0)->after('start_date');
            }
            if (!Schema::hasColumn('payrolls', 'total_haberes')) {
                $table->decimal('total_haberes', 12, 2)->default(0)->after('salario_basico');
            }
            if (!Schema::hasColumn('payrolls', 'total_remunerativo')) {
                $table->decimal('total_remunerativo', 12, 2)->default(0)->after('total_haberes');
            }
            if (!Schema::hasColumn('payrolls', 'total_no_remunerativo')) {
                $table->decimal('total_no_remunerativo', 12, 2)->default(0)->after('total_remunerativo');
            }
            if (!Schema::hasColumn('payrolls', 'total_deducciones')) {
                $table->decimal('total_deducciones', 12, 2)->default(0)->after('total_no_remunerativo');
            }
            if (!Schema::hasColumn('payrolls', 'neto_a_cobrar')) {
                $table->decimal('neto_a_cobrar', 12, 2)->default(0)->after('total_deducciones');
            }
            if (!Schema::hasColumn('payrolls', 'status')) {
                $table->enum('status', ['borrador', 'liquidado', 'pagado'])->default('borrador')->after('neto_a_cobrar');
            }
            if (!Schema::hasColumn('payrolls', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payrolls', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payrolls', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('payrolls', 'liquidated_at')) {
                $table->timestamp('liquidated_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('liquidated_at');
            }
        });

        // Agregar índices usando try-catch para evitar errores si ya existen
        try {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->unique(['employee_id', 'year', 'month'], 'payrolls_employee_period_unique');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->index(['year', 'month'], 'payrolls_period_index');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }

        try {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->index('status', 'payrolls_status_index');
            });
        } catch (\Exception $e) {
            // El índice ya existe, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Eliminar índices (ignorar si no existen)
            try {
                $table->dropUnique('payrolls_employee_period_unique');
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex('payrolls_period_index');
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex('payrolls_status_index');
            } catch (\Exception $e) {}

            // Eliminar foreign keys
            try {
                $table->dropForeign(['employee_id']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropForeign(['created_by']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropForeign(['approved_by']);
            } catch (\Exception $e) {}
        });

        // Eliminar columnas si existen
        $columns = [
            'employee_id', 'year', 'month', 'period_label', 'employee_name',
            'employee_cuil', 'category_name', 'position_name', 'antiguedad_years',
            'start_date', 'salario_basico', 'total_haberes', 'total_remunerativo',
            'total_no_remunerativo', 'total_deducciones', 'neto_a_cobrar',
            'status', 'created_by', 'approved_by', 'approved_at', 'liquidated_at', 'paid_at',
        ];

        Schema::table('payrolls', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
