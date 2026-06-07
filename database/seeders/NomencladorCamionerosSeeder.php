<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Test;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NomencladorCamionerosSeeder extends Seeder
{
    private const NOMENCLADOR_NAME = 'Nomenclador Camioneros';

    private const SOURCE_FILE = 'Nomenclador camioneros.xlsx';

    public function run(): void
    {
        $filePath = base_path(self::SOURCE_FILE);

        if (! file_exists($filePath)) {
            $this->command->error('Archivo no encontrado: '.self::SOURCE_FILE.' (raíz del proyecto)');

            return;
        }

        $this->command->info('Procesando: '.self::SOURCE_FILE.' → '.self::NOMENCLADOR_NAME);
        $this->importNomenclador($filePath);
        $this->command->info('Importación de nomenclador Camioneros completada.');
    }

    private function importNomenclador(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $insurance = Insurance::firstOrCreate(
            ['name' => self::NOMENCLADOR_NAME],
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
                'instructions' => 'Nomenclador de prácticas Obra Social Camioneros. Importado desde '.self::SOURCE_FILE,
            ]
        );

        if ($insurance->type !== 'nomenclador') {
            $insurance->update(['type' => 'nomenclador']);
        }

        $testsCreated = 0;
        $practicesCreated = 0;
        $practicesUpdated = 0;
        $skipped = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $codigo = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nombreDet = trim((string) $sheet->getCell("B{$row}")->getValue());
            $nbuRaw = $sheet->getCell("C{$row}")->getValue();

            if ($codigo === '') {
                $skipped++;

                continue;
            }

            $nbu = $this->parseNbu($nbuRaw);

            $test = Test::where('code', $codigo)->first();

            if ($test) {
                $test->update(['nbu' => $nbu]);
            } else {
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
                    'requires_authorization' => false,
                ]);
                $practicesUpdated++;
            } else {
                InsuranceTest::create([
                    'insurance_id' => $insurance->id,
                    'test_id' => $test->id,
                    'nbu_units' => $nbu,
                    'price' => null,
                    'copago' => 0,
                    'requires_authorization' => false,
                ]);
                $practicesCreated++;
            }
        }

        $this->command->info('  Resultado '.self::NOMENCLADOR_NAME.':');
        $this->command->info("    Tests nuevos creados: {$testsCreated}");
        $this->command->info("    Prácticas creadas: {$practicesCreated}");
        $this->command->info("    Prácticas actualizadas: {$practicesUpdated}");
        if ($skipped > 0) {
            $this->command->warn("    Filas saltadas: {$skipped}");
        }
    }

    private function parseNbu($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $v = trim($value);
            if ($v === '') {
                return 0.0;
            }
            if (str_contains($v, ',') && preg_match('/,\d{1,4}$/', $v)) {
                $clean = str_replace('.', '', $v);

                return (float) str_replace(',', '.', $clean);
            }
            if (str_contains($v, ',')) {
                return (float) str_replace(',', '.', $v);
            }

            return (float) $v;
        }

        return 0.0;
    }
}
