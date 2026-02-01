<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Insurance;

class InsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear la opción "Particular" si no existe
        Insurance::firstOrCreate(
            ['name' => 'particular'],
            [
                'name' => 'particular',
                'type' => 'particular',
                'tax_id' => null,
                'tax' => null,
                'phone' => null,
                'email' => null,
                'address' => null,
                'state' => null,
                'country' => 'argentina',
                'price' => 0,
                'nbu' => 0,
                'nbu_value' => 0,
                'group' => null,
                'instructions' => 'Paciente sin cobertura médica - Pago directo',
            ]
        );

        // Actualizar las obras sociales existentes que no tienen tipo
        Insurance::whereNull('type')
            ->where('name', '!=', 'particular')
            ->update(['type' => 'obra_social']);
    }
}
