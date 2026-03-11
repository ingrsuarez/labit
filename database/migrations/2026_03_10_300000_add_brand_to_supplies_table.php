<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('name');
            $table->boolean('tracks_lot')->default(false)->after('is_active');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('lot_number')->nullable()->after('reason');
            $table->date('expiration_date')->nullable()->after('lot_number');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['brand', 'tracks_lot']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['lot_number', 'expiration_date']);
        });
    }
};
