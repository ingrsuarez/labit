<?php

namespace App\Console\Commands;

use App\Models\ResultBatch;
use App\Models\ResultIngestion;
use Illuminate\Console\Command;

class ApiCleanup extends Command
{
    protected $signature = 'api:cleanup
                            {--days= : Sobrescribe API_LOG_RETENTION_DAYS}
                            {--dry-run : Solo informa cuántos registros serían borrados, no borra nada}';

    protected $description = 'Borra ResultBatch y ResultIngestion más viejos que el período de retención configurado';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('api.log_retention_days', 90));
        $cutoff = now()->subDays($days);

        $this->info("Borrando registros anteriores a {$cutoff->toDateTimeString()} (retención: {$days} días)");

        $ingestionsCount = ResultIngestion::where('created_at', '<', $cutoff)->count();
        $batchesCount = ResultBatch::where('created_at', '<', $cutoff)->count();

        $this->table(['Tabla', 'Registros a borrar'], [
            ['result_ingestions', number_format($ingestionsCount)],
            ['result_batches', number_format($batchesCount)],
        ]);

        if ($this->option('dry-run')) {
            $this->warn('--dry-run activo: no se borró nada.');

            return self::SUCCESS;
        }

        if ($ingestionsCount === 0 && $batchesCount === 0) {
            $this->info('Nada para borrar.');

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction') && ! $this->confirm('¿Confirmás el borrado?', true)) {
            $this->info('Cancelado.');

            return self::FAILURE;
        }

        // Borrar ingestions primero (FK cascadeOnDelete depende del driver, mejor hacerlo explícito)
        $deletedIngestions = ResultIngestion::where('created_at', '<', $cutoff)->delete();
        $deletedBatches = ResultBatch::where('created_at', '<', $cutoff)->delete();

        $this->info("Limpieza completada. Eliminados: {$deletedIngestions} ingestions, {$deletedBatches} batches.");

        return self::SUCCESS;
    }
}
