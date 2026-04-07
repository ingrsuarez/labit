<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('purchase_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreignId('purchase_service_id')->nullable()->after('supply_id')->constrained('purchase_services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_service_id');
        });

        Schema::dropIfExists('purchase_services');
        Schema::dropIfExists('purchase_service_categories');
    }
};
