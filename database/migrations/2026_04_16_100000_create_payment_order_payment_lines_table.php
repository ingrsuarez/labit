<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migración previa pudo crear la tabla y fallar al añadir FK (nombre > 64 chars): queda huérfana y el reintento choca con "already exists".
        Schema::dropIfExists('payment_order_payment_lines');

        Schema::create('payment_order_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_order_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('kind', 32);
            $table->decimal('amount', 14, 2);
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('collection_receipt_payment_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('cheque_due_date')->nullable();
            $table->timestamps();

            // Nombres explícitos: el nombre auto de MySQL para collection_receipt_payment_id supera 64 caracteres.
            $table->foreign('payment_order_id', 'fk_popl_payment_order')
                ->references('id')->on('payment_orders')->cascadeOnDelete();
            $table->foreign('bank_account_id', 'fk_popl_bank_account')
                ->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('collection_receipt_payment_id', 'fk_popl_cr_payment')
                ->references('id')->on('collection_receipt_payments')->nullOnDelete();
        });

        if (! Schema::hasTable('payment_orders')) {
            return;
        }

        $orders = DB::table('payment_orders')->orderBy('id')->get();
        foreach ($orders as $po) {
            if (DB::table('payment_order_payment_lines')->where('payment_order_id', $po->id)->exists()) {
                continue;
            }

            $portfolioIds = DB::table('collection_receipt_payments')
                ->where('payment_order_id', $po->id)
                ->where('line_type', 'echeq')
                ->orderBy('id')
                ->get(['id', 'amount']);

            if ($portfolioIds->isNotEmpty()) {
                $sort = 0;
                foreach ($portfolioIds as $line) {
                    DB::table('payment_order_payment_lines')->insert([
                        'payment_order_id' => $po->id,
                        'sort_order' => $sort++,
                        'kind' => 'portfolio_echeq',
                        'amount' => $line->amount,
                        'bank_account_id' => null,
                        'collection_receipt_payment_id' => $line->id,
                        'payment_reference' => null,
                        'cheque_due_date' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            if ($po->payment_method) {
                DB::table('payment_order_payment_lines')->insert([
                    'payment_order_id' => $po->id,
                    'sort_order' => 0,
                    'kind' => $po->payment_method,
                    'amount' => $po->total,
                    'bank_account_id' => null,
                    'collection_receipt_payment_id' => null,
                    'payment_reference' => $po->payment_reference,
                    'cheque_due_date' => $po->cheque_due_date ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_order_payment_lines');
    }
};
