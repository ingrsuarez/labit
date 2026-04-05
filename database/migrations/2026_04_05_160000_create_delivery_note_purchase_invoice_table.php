<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_note_purchase_invoice')) {
            Schema::create('delivery_note_purchase_invoice', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique('delivery_note_id', 'dn_pi_delivery_note_unique');
                $table->unique(['purchase_invoice_id', 'delivery_note_id'], 'dn_pi_pair_unique');
            });
        }

        $now = now();
        DB::table('purchase_invoices')
            ->whereNotNull('delivery_note_id')
            ->orderBy('id')
            ->select(['id', 'delivery_note_id'])
            ->lazy()
            ->each(function ($row) use ($now) {
                DB::table('delivery_note_purchase_invoice')->insertOrIgnore([
                    'purchase_invoice_id' => $row->id,
                    'delivery_note_id' => $row->delivery_note_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_purchase_invoice');
    }
};
