<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\InsuranceTest;
use App\Models\Test;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NomencladorVeterinarioSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('docs/nomenclador veterinario.xlsx');

        if (! file_exists($filePath)) {
            $this->command->warn('Archivo no encontrado: docs/nomenclador veterinario.xlsx');

            return;
        }

        $this->command->info('Procesando: nomenclador veterinario.xlsx → Veterinario');

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $insurance = Insurance::firstOrCreate(
            ['name' => 'Veterinario'],
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
                'instructions' => 'Nomenclador importado desde nomenclador veterinario.xlsx',
            ]
        );

        if ($insurance->type !== 'nomenclador') {
            $insurance->update(['type' => 'nomenclador']);
        }

        $testsCreated = 0;
        $practicesCreated = 0;
        $practicesUpdated = 0;
        $categoriesUpdated = 0;
        $autoCodeSeq = 1;

        for ($row = 2; $row <= $highestRow; $row++) {
            $codigo = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nbuRaw = $sheet->getCell("B{$row}")->getValue();
            $nombre = trim((string) $sheet->getCell("C{$row}")->getValue());
            $muestra = trim((string) $sheet->getCell("D{$row}")->getValue());

            if (empty($codigo) && empty($nombre)) {
                continue;
            }

            $nbu = $this->parseNbu($nbuRaw);

            if (empty($codigo)) {
                $codigo = sprintf('VET-%03d', $autoCodeSeq++);
                $this->command->warn("  Fila {$row}: sin código, asignado {$codigo} → {$nombre}");
            }

            $test = Test::where('code', $codigo)->first();

            if ($test) {
                $categories = $test->categories ?? [];
                if (! in_array('veterinario', $categories)) {
                    $categories[] = 'veterinario';
                    $test->update(['categories' => $categories]);
                    $categoriesUpdated++;
                }
            } else {
                $test = Test::create([
                    'code' => $codigo,
                    'name' => $nombre ?: "Test {$codigo}",
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
                    'categories' => ['veterinario'],
                ]);
                $testsCreated++;
            }

            $insuranceTest = InsuranceTest::where('insurance_id', $insurance->id)
                ->where('test_id', $test->id)
                ->first();

            if ($insuranceTest) {
                $insuranceTest->update(['nbu_units' => $nbu]);
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

        $this->command->info('  Resultado Veterinario:');
        $this->command->info("    Tests nuevos creados: {$testsCreated}");
        $this->command->info("    Categorías actualizadas (+veterinario): {$categoriesUpdated}");
        $this->command->info("    Prácticas creadas: {$practicesCreated}");
        $this->command->info("    Prácticas actualizadas: {$practicesUpdated}");
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
