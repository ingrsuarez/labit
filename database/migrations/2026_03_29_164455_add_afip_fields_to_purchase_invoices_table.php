<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('cae', 20)->nullable()->after('notes');
            $table->string('cuit_emisor', 13)->nullable()->after('cae');
            $table->json('qr_data')->nullable()->after('cuit_emisor');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['cae', 'cuit_emisor', 'qr_data']);
        });
    }
};
