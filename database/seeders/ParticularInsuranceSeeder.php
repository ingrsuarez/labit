<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class ParticularInsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $existing = Insurance::where('type', 'particular')->first();

        if ($existing) {
            $this->command->info("✓ Ya existe: {$existing->name} (ID {$existing->id})");

            return;
        }

        $insurance = Insurance::create([
            'name' => 'Particular',
            'tax_id' => null,
            'tax' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'state' => null,
            'country' => 'Argentina',
            'price' => 0,
            'nbu' => 1,
            'nbu_value' => 0,
            'nomenclator_id' => null,
            'group' => null,
            'type' => 'particular',
            'instructions' => 'Paciente sin cobertura médica. Pago directo.',
        ]);

        $this->command->info("+ Creado: Particular (ID {$insurance->id})");
    }
}
