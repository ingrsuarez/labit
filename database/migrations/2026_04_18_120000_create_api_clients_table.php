<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key_hash', 64)->unique();
            $table->string('key_preview', 16);
            $table->foreignId('lab_branch_id')->constrained('lab_branches');
            $table->foreignId('company_id')->constrained('companies');
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('requests_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['active', 'lab_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
