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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('afip_activity', 500)->nullable()->after('discount_percent');
            $table->string('cuit_status', 50)->nullable()->after('afip_activity');
            $table->timestamp('afip_verified_at')->nullable()->after('cuit_status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['afip_activity', 'cuit_status', 'afip_verified_at']);
        });
    }
};
