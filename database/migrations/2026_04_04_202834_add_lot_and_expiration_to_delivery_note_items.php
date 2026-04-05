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
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->string('lot_number', 100)->nullable()->after('quantity_received');
            $table->date('expiration_date')->nullable()->after('lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_note_items', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'expiration_date']);
        });
    }
};
