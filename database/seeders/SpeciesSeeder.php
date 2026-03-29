<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['name' => 'Canino', 'code' => 'canino'],
            ['name' => 'Felino', 'code' => 'felino'],
            ['name' => 'Equino', 'code' => 'equino'],
        ];

        foreach ($species as $sp) {
            Species::firstOrCreate(['code' => $sp['code']], $sp);
        }
    }
}
