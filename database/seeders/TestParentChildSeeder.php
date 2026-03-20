<?php

namespace Database\Seeders;

use App\Models\Test;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestParentChildSeeder extends Seeder
{
    private int $created = 0;

    private int $skipped = 0;

    private int $notFound = 0;

    /**
     * Configura relaciones padre-hijo en test_parents para las prácticas del
     * laboratorio clínico, basado en el protocolo ejemplo (docs/Protocolo-ejemplo.pdf).
     *
     * Idempotente: puede ejecutarse múltiples veces sin duplicar relaciones.
     * Tolerante: si un test no existe, loguea warning y continúa.
     */
    public function run(): void
    {
        $hierarchy = [
            'hemograma' => [
                'globulos rojos',
                'hemoglobina, dosaje',
                'hematocrito',
                'vcm',
                'hcm',
                'chcm',
                'rdw-cv',
                'globulos blancos, recuento de',
            ],
            'formula leucocitaria' => [
                'neutrofilos segmentados',
                'eosinofilos, recuento',
                'basofilos',
                'linfocitos',
                'monocitos',
            ],
            'hepatograma' => [
                'bilirrubina total',
                'bilirrubina directa',
                'bilirrubina indirecta',
                'transaminasa, glutamico oxalacetica',
                'transaminasa glutamico piruvica',
                'fosfatasa alcalina (fal)',
                'colesterol total',
                'proteina totales',
            ],
            'orina completa' => [
                'color',
                'aspecto',
                'densidad',
                'ph',
                'glucosa',
                'cetonas',
                'proteinas',
                'bilirrubina',
                'urobilina',
                'hemoglobina',
                'nitritos',
                'celulas epiteliales',
                'leucocitos',
                'eritrocitos',
            ],
            'drogas de abuso screening' => [
                'cocaina',
                'cannabinoides',
            ],
        ];

        $this->command->info('=== TestParentChildSeeder ===');
        $this->command->newLine();

        foreach ($hierarchy as $parentName => $children) {
            $parent = $this->findTest($parentName);

            if (! $parent) {
                $this->command->warn("⚠ Padre no encontrado: '{$parentName}'");
                $this->notFound++;

                continue;
            }

            $this->command->info("▸ Padre: {$parent->name} (ID {$parent->id})");

            foreach ($children as $order => $childName) {
                $child = $this->findTest($childName);

                if (! $child) {
                    $this->command->warn("  ⚠ Hijo no encontrado: '{$childName}'");
                    $this->notFound++;

                    continue;
                }

                if ($child->id === $parent->id) {
                    $this->command->warn("  ⚠ Hijo es el mismo padre, saltando: '{$childName}'");
                    $this->notFound++;

                    continue;
                }

                $exists = DB::table('test_parents')
                    ->where('parent_test_id', $parent->id)
                    ->where('child_test_id', $child->id)
                    ->exists();

                if ($exists) {
                    $this->command->info("  ✓ Ya existe: {$child->name}");
                    $this->skipped++;

                    continue;
                }

                DB::table('test_parents')->insert([
                    'parent_test_id' => $parent->id,
                    'child_test_id' => $child->id,
                    'order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("  + Creada: {$child->name} (orden {$order})");
                $this->created++;
            }

            $this->command->newLine();
        }

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════');
        $this->command->info("Relaciones creadas: {$this->created}");
        $this->command->info("Ya existentes:      {$this->skipped}");
        $this->command->warn("No encontradas:     {$this->notFound}");
        $this->command->info('══════════════════════════════════════');

        if ($this->notFound > 0) {
            $this->command->newLine();
            $this->command->warn('Los tests no encontrados deben agregarse a la tabla `tests` antes de re-ejecutar este seeder.');
        }
    }

    /**
     * Busca un test por nombre con validación estricta:
     * 1. Match exacto (case-insensitive)
     * 2. LIKE con validación de relevancia (el término debe cubrir >= 40% del nombre)
     */
    private function findTest(string $name): ?Test
    {
        $exact = Test::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($exact) {
            if (mb_strlen($name) <= 3 && $this->isChildOfAnotherParent($exact->id)) {
                return null;
            }

            return $exact;
        }

        $matches = Test::where('name', 'LIKE', "%{$name}%")->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $validated = $matches->filter(function (Test $test) use ($name) {
            $ratio = mb_strlen($name) / mb_strlen($test->name);

            return $ratio >= 0.4;
        });

        if ($validated->isEmpty()) {
            return null;
        }

        if ($validated->count() === 1) {
            return $validated->first();
        }

        $startsWith = $validated->filter(
            fn (Test $t) => str_starts_with(strtolower($t->name), strtolower($name))
        );

        if ($startsWith->count() === 1) {
            return $startsWith->first();
        }

        $this->command->warn("  ⚠ Múltiples matches para '{$name}' — saltando");

        return null;
    }

    private function isChildOfAnotherParent(int $testId): bool
    {
        return DB::table('test_parents')->where('child_test_id', $testId)->exists();
    }
}
