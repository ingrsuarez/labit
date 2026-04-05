<?php

/**
 * v1.37.0 — Stock por sede (infra).
 *
 * Backfill (si existe al menos una sede activa):
 * - Sede por defecto: activa con is_central = true, si no la primera activa por id.
 * - Movimientos y documentos de compra existentes reciben esa sede.
 * - supply_lab_branch_stock: una fila por insumo con stock > 0, cantidad = supplies.stock en esa sede.
 * - supplies.stock permanece como suma cache (opción A); tras el backfill coincide con la única fila pivot.
 *
 * Si no hay sedes en lab_branches, las columnas quedan NULL (entornos de test / legado).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_lab_branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_branch_id')->constrained('lab_branches')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['supply_id', 'lab_branch_id'], 'slbs_supply_branch_uq');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('supply_id')->constrained('lab_branches')->nullOnDelete();
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['lab_branch_id', 'supply_id', 'created_at'], 'sm_br_supply_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('company_id')->constrained('lab_branches')->nullOnDelete();
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('company_id')->constrained('lab_branches')->nullOnDelete();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('lab_branch_id')->nullable()->after('company_id')->constrained('lab_branches')->nullOnDelete();
        });

        $defaultBranchId = DB::table('lab_branches')
            ->where('is_active', true)
            ->where('is_central', true)
            ->orderBy('id')
            ->value('id');

        if (! $defaultBranchId) {
            $defaultBranchId = DB::table('lab_branches')
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }

        if (! $defaultBranchId) {
            return;
        }

        DB::table('stock_movements')->whereNull('lab_branch_id')->update(['lab_branch_id' => $defaultBranchId]);
        DB::table('purchase_orders')->whereNull('lab_branch_id')->update(['lab_branch_id' => $defaultBranchId]);
        DB::table('delivery_notes')->whereNull('lab_branch_id')->update(['lab_branch_id' => $defaultBranchId]);
        DB::table('purchase_invoices')->whereNull('lab_branch_id')->update(['lab_branch_id' => $defaultBranchId]);

        $now = now();
        foreach (DB::table('supplies')->orderBy('id')->cursor() as $supply) {
            $qty = (float) $supply->stock;
            if ($qty <= 0) {
                continue;
            }
            DB::table('supply_lab_branch_stock')->insert([
                'supply_id' => $supply->id,
                'lab_branch_id' => $defaultBranchId,
                'quantity' => $qty,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('sm_br_supply_created_idx');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_branch_id');
        });

        Schema::dropIfExists('supply_lab_branch_stock');
    }
};
