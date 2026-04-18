<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            if (! $this->indexExists('result_ingestions', 'idx_dashboard_status_created')) {
                $table->index(['status', 'created_at'], 'idx_dashboard_status_created');
            }
            if (! $this->indexExists('result_ingestions', 'idx_dashboard_client_created')) {
                $table->index(['api_client_id', 'created_at'], 'idx_dashboard_client_created');
            }
            if (! $this->indexExists('result_ingestions', 'idx_dashboard_rejection_created')) {
                $table->index(['rejection_reason', 'created_at'], 'idx_dashboard_rejection_created');
            }
        });

        Schema::table('result_batches', function (Blueprint $table) {
            if (! $this->indexExists('result_batches', 'idx_batches_client_created')) {
                $table->index(['api_client_id', 'created_at'], 'idx_batches_client_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('result_ingestions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_dashboard_status_created');
            $table->dropIndexIfExists('idx_dashboard_client_created');
            $table->dropIndexIfExists('idx_dashboard_rejection_created');
        });

        Schema::table('result_batches', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_batches_client_created');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn ($i) => $i['name'] === $index);
    }
};
