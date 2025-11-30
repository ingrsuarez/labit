<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para el sistema de aprobación de vacaciones
     */
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->timestamp('requested_at')->nullable()->after('rejection_reason');
            $table->boolean('signature_required')->default(false)->after('requested_at');
            $table->timestamp('signed_at')->nullable()->after('signature_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason', 'requested_at', 'signature_required', 'signed_at']);
        });
    }
};
