<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\Insurance;
use App\Models\InsuranceTest;
use Illuminate\Support\Facades\Log;

class Nomenclador20162023Seeder extends Seeder
{
    /**
     * Seeder para importar las determinaciones del Nomenclador 2016-2023
     * 
     * Este seeder:
     * 1. Crea/actualiza las determinaciones de laboratorio desde el CSV
     * 2. Crea una obra social "Nomenclador Base 20162023" como referencia
     * 3. Asocia cada práctica con su valor NBU en el nomenclador
     * 
     * @return void
     */
    public function run(): void
    {
        $csvPath = database_path('../docs/nomenclador20162023.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("No se encontró el archivo CSV en: {$csvPath}");
            return;
        }

        $this->command->info('Iniciando importación del Nomenclador 2016-2023...');
        
        // Leer el archivo CSV
        $file = fopen($csvPath, 'r');
        if (!$file) {
            $this->command->error("No se pudo abrir el archivo CSV");
            return;
        }

        // Saltar la primera línea (encabezados)
        $header = fgetcsv($file, 0, ';');
        $this->command->info("Encabezados: " . implode(', ', $header));

        $testsCreated = 0;
        $testsUpdated = 0;
        $testIds = [];

        // Procesar cada línea del CSV
        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Saltar filas vacías
            if (empty($row[0]) || empty($row[1])) {
                continue;
            }

            $codigo = trim($row[0]);
            $nombre = trim($row[1]);
            // Convertir el NBU de formato europeo (coma) a formato PHP (punto)
            $nbuString = isset($row[2]) ? trim($row[2]) : '0';
            $nbu = (float) str_replace(',', '.', $nbuString);

            // Buscar si ya existe el test por código
            $test = Test::where('code', $codigo)->first();

            if ($test) {
                // Actualizar solo el NBU si el test ya existe
                $test->update([
                    'nbu' => $nbu,
                ]);
                $testsUpdated++;
            } else {
                // Crear nuevo test sin padre, sin unidades, sin valores de referencia
                $test = Test::create([
                    'code' => $codigo,
                    'name' => $nombre,
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

            $testIds[$test->id] = $nbu;
        }

        fclose($file);

        $this->command->info("Tests creados: {$testsCreated}");
        $this->command->info("Tests actualizados (NBU): {$testsUpdated}");

        // Crear el nomenclador "Nomenclador 20162023" como lista de precios de referencia
        $this->command->info('Creando/actualizando Nomenclador 20162023...');
        
        $insurance = Insurance::firstOrCreate(
            ['name' => 'Nomenclador 20162023'],
            [
                'name' => 'Nomenclador 20162023',
                'type' => 'nomenclador', // Tipo nomenclador - NO es una obra social
                'tax_id' => null,
                'tax' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
                'state' => null,
                'country' => 'argentina',
                'price' => 0,
                'nbu' => 0,
                'nbu_value' => 1, // Valor base de 1 para que NBU = precio base
                'group' => null,
                'instructions' => 'Nomenclador bioquímico de referencia 2016-2023. Los valores NBU representan las unidades de cada práctica.',
            ]
        );
        
        // Actualizar el tipo si ya existía con otro nombre o tipo
        if ($insurance->type !== 'nomenclador') {
            $insurance->update(['type' => 'nomenclador']);
        }

        $this->command->info("Obra social ID: {$insurance->id}");

        // Asociar cada test con el nomenclador
        $this->command->info('Asociando prácticas al nomenclador...');
        
        $nomencladorCreated = 0;
        $nomencladorUpdated = 0;

        foreach ($testIds as $testId => $nbuUnits) {
            $insuranceTest = InsuranceTest::where('insurance_id', $insurance->id)
                ->where('test_id', $testId)
                ->first();

            if ($insuranceTest) {
                $insuranceTest->update([
                    'nbu_units' => $nbuUnits,
                ]);
                $nomencladorUpdated++;
            } else {
                InsuranceTest::create([
                    'insurance_id' => $insurance->id,
                    'test_id' => $testId,
                    'nbu_units' => $nbuUnits,
                    'price' => null, // Se calculará automáticamente desde nbu_units × nbu_value
                    'copago' => 0,
                    'requires_authorization' => false,
                ]);
                $nomencladorCreated++;
            }
        }

        $this->command->info("Nomenclador - Prácticas creadas: {$nomencladorCreated}");
        $this->command->info("Nomenclador - Prácticas actualizadas: {$nomencladorUpdated}");
        $this->command->info('¡Importación completada exitosamente!');
    }
}
