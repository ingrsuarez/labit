<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_of_sale', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('point_of_sale_id')->nullable()->after('point_of_sale')->constrained('points_of_sale')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['point_of_sale_id']);
            $table->dropColumn('point_of_sale_id');
        });

        Schema::dropIfExists('points_of_sale');
    }
};
