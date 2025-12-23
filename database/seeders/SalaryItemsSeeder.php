<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalaryItem;

class SalaryItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Haberes
        $haberes = [
            [
                'name' => 'Zona 30%',
                'code' => 'Z30',
                'type' => 'haber',
                'calculation_type' => 'percentage',
                // CCT 108/75 FATSA: El 30% de zona se calcula sobre Básico + Antigüedad + Adicional Título
                'calculation_base' => 'basic_antiguedad_titulo',
                'value' => 30,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 10, // Se calcula después de antigüedad y adicional título
            ],
            [
                'name' => 'Puesto Jerárquico',
                'code' => 'PJER',
                'type' => 'haber',
                'calculation_type' => 'percentage',
                'calculation_base' => 'basic', // Solo Básico
                'value' => 15.2,
                'is_remunerative' => true,
                'is_active' => true,
                'requires_assignment' => true,
                'hide_percentage_in_receipt' => true, // Acuerdo interno de la empresa
                'order' => 20, // Se calcula después de zona
            ],
            [
                'name' => 'Adicional Título',
                'code' => 'ADTIT',
                'type' => 'haber',
                'calculation_type' => 'percentage',
                'calculation_base' => 'basic', // Solo Básico
                'value' => 15,
                'is_remunerative' => true,
                'is_active' => true,
                // CCT 108/75 FATSA: El adicional título se incluye en la base de antigüedad y zona
                'includes_in_antiguedad_base' => true,
                'order' => 2, // Se calcula antes de antigüedad y zona
            ],
        ];

        foreach ($haberes as $haber) {
            SalaryItem::updateOrCreate(
                ['code' => $haber['code']],
                $haber
            );
        }

        $this->command->info('✓ Haberes cargados correctamente');

        // Deducciones (todas sobre subtotal remunerativo)
        $deducciones = [
            [
                'name' => 'Jubilación',
                'code' => 'JUB',
                'type' => 'deduccion',
                'calculation_type' => 'percentage',
                'calculation_base' => 'subtotal',
                'value' => 11,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'INSS/PLEY 19032',
                'code' => 'INSS',
                'type' => 'deduccion',
                'calculation_type' => 'percentage',
                'calculation_base' => 'subtotal',
                'value' => 3,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Ley 23660',
                'code' => 'L23660',
                'type' => 'deduccion',
                'calculation_type' => 'percentage',
                'calculation_base' => 'subtotal',
                'value' => 2.55,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'Aporte Solidario',
                'code' => 'APSOL',
                'type' => 'deduccion',
                'calculation_type' => 'percentage',
                'calculation_base' => 'subtotal',
                'value' => 1,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'ANSALL',
                'code' => 'ANSALL',
                'type' => 'deduccion',
                'calculation_type' => 'percentage',
                'calculation_base' => 'subtotal',
                'value' => 0.45,
                'is_remunerative' => true,
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($deducciones as $deduccion) {
            SalaryItem::updateOrCreate(
                ['code' => $deduccion['code']],
                $deduccion
            );
        }

        $this->command->info('✓ Deducciones cargadas correctamente');
    }
}

