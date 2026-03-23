<?php

namespace Database\Seeders;

use App\Models\Test;
use Illuminate\Database\Seeder;

class MissingTestsSeeder extends Seeder
{
    private int $created = 0;

    private int $existing = 0;

    private int $errors = 0;

    public function run(): void
    {
        $testsToCreate = [
            'hemograma' => [
                ['name' => 'vcm', 'code' => 'VCM'],
                ['name' => 'hcm', 'code' => 'HCM'],
                ['name' => 'chcm', 'code' => 'CHCM'],
                ['name' => 'rdw-cv', 'code' => 'RDW'],
            ],
            'formula leucocitaria' => [
                ['name' => 'neutrofilos segmentados', 'code' => 'NEUTSE'],
                ['name' => 'basofilos', 'code' => 'BASO'],
                ['name' => 'linfocitos', 'code' => 'LINF'],
                ['name' => 'monocitos', 'code' => 'MONO'],
            ],
            'hepatograma' => [
                ['name' => 'bilirrubina total', 'code' => 'BILTOT'],
                ['name' => 'bilirrubina directa', 'code' => 'BILDIR'],
                ['name' => 'bilirrubina indirecta', 'code' => 'BILIND'],
            ],
            'orina completa' => [
                ['name' => 'color', 'code' => 'ORI-COL'],
                ['name' => 'aspecto', 'code' => 'ORI-ASP'],
                ['name' => 'densidad', 'code' => 'ORI-DEN'],
                ['name' => 'ph', 'code' => 'ORI-PH'],
                ['name' => 'glucosa', 'code' => 'ORI-GLU'],
                ['name' => 'cetonas', 'code' => 'ORI-CET'],
                ['name' => 'proteinas', 'code' => 'ORI-PRO'],
                ['name' => 'bilirrubina', 'code' => 'ORI-BIL'],
                ['name' => 'urobilina', 'code' => 'ORI-URO'],
                ['name' => 'hemoglobina', 'code' => 'ORI-HEM'],
                ['name' => 'nitritos', 'code' => 'ORI-NIT'],
                ['name' => 'celulas epiteliales', 'code' => 'ORI-CEL'],
                ['name' => 'leucocitos', 'code' => 'ORI-LEU'],
                ['name' => 'eritrocitos', 'code' => 'ORI-ERI'],
            ],
            'drogas de abuso screening' => [
                ['name' => 'cocaina', 'code' => 'DRG-COC'],
                ['name' => 'cannabinoides', 'code' => 'DRG-CAN'],
            ],
        ];

        $this->command->info('=== MissingTestsSeeder ===');
        $this->command->newLine();

        foreach ($testsToCreate as $parentName => $children) {
            $parent = Test::whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($parentName).'%'])->first();

            if (! $parent) {
                $this->command->warn("⚠ Padre no encontrado: '{$parentName}'");
                $this->errors += count($children);

                continue;
            }

            $materialId = $parent->material;
            $this->command->info("▸ Padre: {$parent->name} (ID {$parent->id}, material: {$materialId})");

            foreach ($children as $child) {
                $existingTest = Test::whereRaw('LOWER(name) = ?', [strtolower($child['name'])])->first();

                if ($existingTest) {
                    $this->command->info("  ✓ Ya existe: {$existingTest->code} — {$existingTest->name}");
                    $this->existing++;

                    continue;
                }

                $code = $this->resolveUniqueCode($child['code']);

                Test::create([
                    'name' => $child['name'],
                    'code' => $code,
                    'material' => $materialId,
                    'decimals' => 2,
                    'price' => 0,
                    'cost' => 0,
                ]);

                $this->command->info("  + Creado: {$code} — {$child['name']}");
                $this->created++;
            }

            $this->command->newLine();
        }

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════');
        $this->command->info("Creados:        {$this->created}");
        $this->command->info("Ya existentes:  {$this->existing}");
        if ($this->errors > 0) {
            $this->command->warn("Errores:        {$this->errors}");
        }
        $this->command->info('══════════════════════════════════════');
    }

    private function resolveUniqueCode(string $code): string
    {
        if (! Test::where('code', $code)->exists()) {
            return $code;
        }

        $suffix = 1;
        while (Test::where('code', "{$code}-{$suffix}")->exists()) {
            $suffix++;
        }

        return "{$code}-{$suffix}";
    }
}
