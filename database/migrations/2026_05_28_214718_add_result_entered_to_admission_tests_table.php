<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            $table->foreignId('result_entered_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('result_entered_at')->nullable()->after('result_entered_by');
        });
    }

    public function down(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            $table->dropForeign(['result_entered_by']);
            $table->dropColumn(['result_entered_by', 'result_entered_at']);
        });
    }
};
