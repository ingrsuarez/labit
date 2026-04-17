<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_order_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained('payment_orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('kind', 32);
            $table->decimal('amount', 14, 2);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('collection_receipt_payment_id')->nullable()->constrained('collection_receipt_payments')->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->date('cheque_due_date')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('payment_orders')) {
            return;
        }

        $orders = DB::table('payment_orders')->orderBy('id')->get();
        foreach ($orders as $po) {
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
