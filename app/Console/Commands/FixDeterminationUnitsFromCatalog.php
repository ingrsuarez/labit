<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDeterminationUnitsFromCatalog extends Command
{
    protected $signature = 'labit:fix-determination-units
                            {--dry-run : Mostrar cuántos registros se afectarían sin modificar nada}';

    protected $description = 'Limpia las columnas unit en admission_tests, sample_determinations y vet_admission_tests que fueron sobreescritas por LISCOM. La unidad oficial proviene siempre de tests.unit (catálogo).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $tables = [
            'admission_tests' => 'admission_tests tiene unit != NULL (sobreescrita por equipo)',
            'sample_determinations' => 'sample_determinations tiene unit != NULL (sobreescrita por equipo)',
            'vet_admission_tests' => 'vet_admission_tests tiene unit != NULL (sobreescrita por equipo)',
        ];

        $totalAffected = 0;

        foreach ($tables as $table => $label) {
            $count = DB::table($table)->whereNotNull('unit')->count();
            $this->line("  <info>{$table}</info>: {$count} filas con unit != NULL");
            $totalAffected += $count;

            if (! $dryRun && $count > 0) {
                DB::table($table)->whereNotNull('unit')->update(['unit' => null]);
            }
        }

        if ($dryRun) {
            $this->warn("--dry-run: no se modificó nada. Total filas afectadas: {$totalAffected}");
        } else {
            $this->info("✔ Limpieza completada. {$totalAffected} filas actualizadas (unit → NULL).");
            $this->info('  La unidad ahora proviene exclusivamente del catálogo (tests.unit).');
        }

        return self::SUCCESS;
    }
}
