<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ObrasSocialesExcelSeeder extends Seeder
{
    /**
     * Mapeo de la columna NOMENCLADOR del Excel → nombre del Insurance con type='nomenclador'.
     * Debe coincidir con los nombres usados en NomencladoresExcelSeeder y el existente.
     */
    private array $nomencladorMap = [
        'ISSN2020' => 'ISSN',
        '2016Uni' => 'Nomenclador 2016 Uni',
        '20162023' => 'Nomenclador 20162023',
        'PAMI' => 'PAMI',
        'Omint' => 'OMINT',
        'Medicus' => 'Medicus',
        '2012Reduc' => 'Nomenclador 2012 Reducido',
        '2016' => 'Nomenclador 2016',
        'SWISS2012' => 'Swiss Medical',
    ];

    public function run(): void
    {
        $filePath = base_path('docs/obras sociales.xlsx');

        if (! file_exists($filePath)) {
            $this->command->error('No se encontró el archivo: docs/obras sociales.xlsx');

            return;
        }

        $this->command->info('Cargando obras sociales desde Excel...');

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $nomencladorIds = $this->resolveNomencladorIds();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $sigla = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nombre = trim((string) $sheet->getCell("B{$row}")->getValue());
            $cuit = trim((string) $sheet->getCell("C{$row}")->getValue());
            $nomKey = trim((string) $sheet->getCell("D{$row}")->getValue());
            $pmo = (float) $sheet->getCell("E{$row}")->getValue();

            if (empty($sigla) && empty($nombre)) {
                continue;
            }

            $nomenclatorId = $nomencladorIds[$nomKey] ?? null;
            if (! $nomenclatorId && ! empty($nomKey)) {
                $this->command->warn("  Fila {$row}: nomenclador '{$nomKey}' no encontrado en BD, se asigna null");
            }

            $insurance = Insurance::where('name', $nombre)->first();

            if ($insurance) {
                $insurance->update([
                    'tax_id' => $cuit ?: $insurance->tax_id,
                    'nbu_value' => $pmo,
                    'nomenclator_id' => $nomenclatorId ?? $insurance->nomenclator_id,
                    'type' => $insurance->type === 'particular' ? 'particular' : 'obra_social',
                ]);
                $updated++;
            } else {
                Insurance::create([
                    'name' => $nombre,
                    'tax_id' => $cuit ?: null,
                    'tax' => null,
                    'phone' => null,
                    'email' => null,
                    'address' => null,
                    'state' => null,
                    'country' => 'argentina',
                    'price' => 0,
                    'nbu' => 0,
                    'nbu_value' => $pmo,
                    'nomenclator_id' => $nomenclatorId,
                    'group' => null,
                    'type' => strtolower($sigla) === 'particular' ? 'particular' : 'obra_social',
                    'instructions' => $sigla !== $nombre ? "Sigla: {$sigla}" : null,
                ]);
                $created++;
            }
        }

        $this->command->info("Obras sociales creadas: {$created}");
        $this->command->info("Obras sociales actualizadas: {$updated}");
        if ($skipped > 0) {
            $this->command->warn("Filas saltadas: {$skipped}");
        }
        $this->command->info('Importación de obras sociales completada.');
    }

    private function resolveNomencladorIds(): array
    {
        $ids = [];
        foreach ($this->nomencladorMap as $excelKey => $insuranceName) {
            $insurance = Insurance::where('name', $insuranceName)
                ->where('type', 'nomenclador')
                ->first();

            if ($insurance) {
                $ids[$excelKey] = $insurance->id;
            } else {
                $this->command->warn("Nomenclador base '{$insuranceName}' (clave: {$excelKey}) no encontrado en BD");
            }
        }

        return $ids;
    }
}
