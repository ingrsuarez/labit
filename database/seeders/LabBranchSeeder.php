<?php

namespace Database\Seeders;

use App\Models\LabBranch;
use Illuminate\Database\Seeder;

class LabBranchSeeder extends Seeder
{
    public function run(): void
    {
        LabBranch::firstOrCreate(
            ['is_central' => true],
            [
                'name'      => 'Laboratorio Central',
                'is_active' => true,
            ]
        );
    }
}
