<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_receipt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_receipt_id')->constrained()->cascadeOnDelete();
            $table->enum('line_type', ['efectivo', 'transferencia', 'echeq']);
            $table->decimal('amount', 14, 2);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('cheque_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $receipts = DB::table('collection_receipts')->select('id', 'date', 'total', 'payment_method', 'payment_reference')->get();

        foreach ($receipts as $r) {
            $total = (float) $r->total;
            if ($total <= 0.009) {
                continue;
            }

            $method = $r->payment_method;
            $lineType = 'efectivo';
            $bankAccountId = null;
            $chequeNumber = null;
            $bankName = null;
            $dueDate = null;

            if (in_array($method, ['transferencia', 'deposito', 'tarjeta'], true)) {
                $lineType = 'transferencia';
            } elseif ($method === 'cheque') {
                $lineType = 'echeq';
                $chequeNumber = $r->payment_reference ? mb_substr((string) $r->payment_reference, 0, 191) : '—';
                $bankName = 'Cheque / legado';
                $dueDate = $r->date;
            } else {
                $lineType = 'efectivo';
            }

            DB::table('collection_receipt_payments')->insert([
                'collection_receipt_id' => $r->id,
                'line_type' => $lineType,
                'amount' => $total,
                'bank_account_id' => $bankAccountId,
                'cheque_number' => $chequeNumber,
                'bank_name' => $bankName,
                'due_date' => $dueDate,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_receipt_payments');
    }
};
