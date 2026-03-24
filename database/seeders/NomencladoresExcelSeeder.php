<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Test;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NomencladoresExcelSeeder extends Seeder
{
    private array $nomencladores = [
        'pami.xlsx' => 'PAMI',
        'medicus.xlsx' => 'Medicus',
        'omint.xlsx' => 'OMINT',
        'swiss2012.xlsx' => 'Swiss Medical',
        'Issn2020.xlsx' => 'ISSN',
        '2016.xlsx' => 'Nomenclador 2016',
        '2016uni.xlsx' => 'Nomenclador 2016 Uni',
        '2012reduc.xlsx' => 'Nomenclador 2012 Reducido',
    ];

    public function run(): void
    {
        $docsPath = base_path('docs');

        foreach ($this->nomencladores as $filename => $nombre) {
            $filePath = $docsPath.DIRECTORY_SEPARATOR.$filename;

            if (! file_exists($filePath)) {
                $this->command->warn("Archivo no encontrado: {$filename}, saltando...");

                continue;
            }

            $this->command->info("Procesando: {$filename} → {$nombre}");
            $this->importNomenclador($filePath, $nombre, $filename);
        }

        $this->command->info('¡Importación de todos los nomencladores completada!');
    }

    private function importNomenclador(string $filePath, string $nombre, string $filename): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $insurance = Insurance::firstOrCreate(
            ['name' => $nombre],
            [
                'type' => 'nomenclador',
                'tax_id' => null,
                'tax' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
                'state' => null,
                'country' => 'argentina',
                'price' => 0,
                'nbu' => 0,
                'nbu_value' => 1,
                'group' => null,
                'instructions' => "Nomenclador importado desde {$filename}",
            ]
        );

        if ($insurance->type !== 'nomenclador') {
            $insurance->update(['type' => 'nomenclador']);
        }

        $hasAuthColumn = in_array($filename, ['pami.xlsx', 'medicus.xlsx', 'omint.xlsx', 'swiss2012.xlsx']);

        $testsCreated = 0;
        $practicesCreated = 0;
        $practicesUpdated = 0;
        $skipped = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $codigo = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nombreDet = trim((string) $sheet->getCell("B{$row}")->getValue());
            $nbuRaw = $sheet->getCell("C{$row}")->getValue();

            if (empty($codigo) && empty($nombreDet)) {
                continue;
            }

            if (empty($codigo)) {
                $skipped++;

                continue;
            }

            $nbu = $this->parseNbu($nbuRaw);

            $requiresAuth = false;
            if ($hasAuthColumn) {
                $authVal = strtoupper(trim((string) $sheet->getCell("D{$row}")->getValue()));
                $requiresAuth = $authVal === 'SI';
            }

            $test = Test::where('code', $codigo)->first();

            if (! $test) {
                $test = Test::create([
                    'code' => $codigo,
                    'name' => $nombreDet ?: "Test {$codigo}",
                    'nbu' => $nbu,
                    'unit' => null,
                    'parent' => null,
                    'low' => null,
                    'high' => null,
                    'instructions' => null,
                    'default_reference_category_id' => null,
                    'decimals' => null,
                    'negative' => null,
                    'positive' => null,
                    'questions' => null,
                    'method' => null,
                    'price' => null,
                    'cost' => null,
                    'work_sheet' => null,
                    'material' => null,
                    'formula' => null,
                    'box' => null,
                ]);
                $testsCreated++;
            }

            $insuranceTest = InsuranceTest::where('insurance_id', $insurance->id)
                ->where('test_id', $test->id)
                ->first();

            if ($insuranceTest) {
                $insuranceTest->update([
                    'nbu_units' => $nbu,
                    'requires_authorization' => $requiresAuth,
                ]);
                $practicesUpdated++;
            } else {
                InsuranceTest::create([
                    'insurance_id' => $insurance->id,
                    'test_id' => $test->id,
                    'nbu_units' => $nbu,
                    'price' => null,
                    'copago' => 0,
                    'requires_authorization' => $requiresAuth,
                ]);
                $practicesCreated++;
            }
        }

        $this->command->info("  Resultado {$nombre}:");
        $this->command->info("    Tests nuevos creados: {$testsCreated}");
        $this->command->info("    Prácticas creadas: {$practicesCreated}");
        $this->command->info("    Prácticas actualizadas: {$practicesUpdated}");
        if ($skipped > 0) {
            $this->command->warn("    Filas saltadas: {$skipped}");
        }
    }

    private function parseNbu($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = str_replace('.', '', $value);
            $clean = str_replace(',', '.', $clean);

            return (float) $clean;
        }

        return 0;
    }
}
