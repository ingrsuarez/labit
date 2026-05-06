<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_perceptions', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->after('accounting_account_id')
                ->constrained('taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_perceptions', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
        });
    }
};
