<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaysSeeder extends Seeder
{
    /**
     * Feriados de Argentina 2025
     */
    public function run(): void
    {
        $holidays = [
            // 2025 - Feriados inamovibles
            ['date' => '2025-01-01', 'name' => 'Año Nuevo', 'type' => 'fijo'],
            ['date' => '2025-03-03', 'name' => 'Carnaval', 'type' => 'fijo'],
            ['date' => '2025-03-04', 'name' => 'Carnaval', 'type' => 'fijo'],
            ['date' => '2025-03-24', 'name' => 'Día Nacional de la Memoria', 'type' => 'fijo'],
            ['date' => '2025-04-02', 'name' => 'Día del Veterano y de los Caídos en Malvinas', 'type' => 'fijo'],
            ['date' => '2025-04-18', 'name' => 'Viernes Santo', 'type' => 'fijo'],
            ['date' => '2025-05-01', 'name' => 'Día del Trabajador', 'type' => 'fijo'],
            ['date' => '2025-05-25', 'name' => 'Día de la Revolución de Mayo', 'type' => 'fijo'],
            ['date' => '2025-06-16', 'name' => 'Paso a la Inmortalidad del Gral. Güemes', 'type' => 'movil'],
            ['date' => '2025-06-20', 'name' => 'Paso a la Inmortalidad del Gral. Belgrano', 'type' => 'fijo'],
            ['date' => '2025-07-09', 'name' => 'Día de la Independencia', 'type' => 'fijo'],
            ['date' => '2025-08-18', 'name' => 'Paso a la Inmortalidad del Gral. San Martín', 'type' => 'movil'],
            ['date' => '2025-10-12', 'name' => 'Día del Respeto a la Diversidad Cultural', 'type' => 'fijo'],
            ['date' => '2025-11-20', 'name' => 'Día de la Soberanía Nacional', 'type' => 'movil'],
            ['date' => '2025-12-08', 'name' => 'Inmaculada Concepción de María', 'type' => 'fijo'],
            ['date' => '2025-12-25', 'name' => 'Navidad', 'type' => 'fijo'],
            
            // 2024 - Para vacaciones ya tomadas
            ['date' => '2024-01-01', 'name' => 'Año Nuevo', 'type' => 'fijo'],
            ['date' => '2024-02-12', 'name' => 'Carnaval', 'type' => 'fijo'],
            ['date' => '2024-02-13', 'name' => 'Carnaval', 'type' => 'fijo'],
            ['date' => '2024-03-24', 'name' => 'Día Nacional de la Memoria', 'type' => 'fijo'],
            ['date' => '2024-03-28', 'name' => 'Jueves Santo', 'type' => 'fijo'],
            ['date' => '2024-03-29', 'name' => 'Viernes Santo', 'type' => 'fijo'],
            ['date' => '2024-04-02', 'name' => 'Día del Veterano y de los Caídos en Malvinas', 'type' => 'fijo'],
            ['date' => '2024-05-01', 'name' => 'Día del Trabajador', 'type' => 'fijo'],
            ['date' => '2024-05-25', 'name' => 'Día de la Revolución de Mayo', 'type' => 'fijo'],
            ['date' => '2024-06-17', 'name' => 'Paso a la Inmortalidad del Gral. Güemes', 'type' => 'movil'],
            ['date' => '2024-06-20', 'name' => 'Paso a la Inmortalidad del Gral. Belgrano', 'type' => 'fijo'],
            ['date' => '2024-07-09', 'name' => 'Día de la Independencia', 'type' => 'fijo'],
            ['date' => '2024-08-17', 'name' => 'Paso a la Inmortalidad del Gral. San Martín', 'type' => 'movil'],
            ['date' => '2024-10-12', 'name' => 'Día del Respeto a la Diversidad Cultural', 'type' => 'fijo'],
            ['date' => '2024-11-18', 'name' => 'Día de la Soberanía Nacional', 'type' => 'movil'],
            ['date' => '2024-12-08', 'name' => 'Inmaculada Concepción de María', 'type' => 'fijo'],
            ['date' => '2024-12-25', 'name' => 'Navidad', 'type' => 'fijo'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'type' => $holiday['type'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Feriados de Argentina 2024-2025 cargados correctamente.');
    }
}

