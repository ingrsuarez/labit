<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nota: ajustá los básicos al período que vayas a liquidar.
        // Aquí uso los valores de DIC-2024 del acuerdo subido.
        $rows = [
            [
                'name'        => 'Profesional Bioquímico',
                'agreement'   => 'CCT 108/75 (FATSA–CADIME/CEDIM)',
                'union_name'  => 'FATSA / ATSA',
                'wage'        => 1184851.92, // PROF. BIOQ./NUT./KINE - dic/24
                'full_time'   => '36h',      // mismo régimen que técnico de lab. (ver notas)
            ],
            [
                'name'        => 'Técnico de Laboratorio',
                'agreement'   => 'CCT 108/75 (FATSA–CADIME/CEDIM)',
                'union_name'  => 'FATSA / ATSA',
                'wage'        => 1077405.79,  // PRIMERA CATEGORÍA - dic/24
                'full_time'   => '36h',      // 36 hs. semanales como base en el CCT
            ],
            [
                'name'        => 'Secretaría (Administrativo de 1ra - encuadre 2da cat.)',
                'agreement'   => 'CCT 108/75 (FATSA–CADIME/CEDIM)',
                'union_name'  => 'FATSA / ATSA',
                'wage'        => 1030191.35,  // SEGUNDA CATEGORÍA - dic/24 (administrativos de 1ra encuadran acá)
                'full_time'   => '48h',      // jornada administrativa típica
            ],
        ];

        foreach ($rows as $r) {
            Category::updateOrCreate(
                ['name' => $r['name']],
                [
                    'agreement'   => $r['agreement'],
                    'union_name'  => $r['union_name'],
                    'wage'        => $r['wage'],
                    'full_time'   => $r['full_time'],
                ]
            );
        }
    }
}
