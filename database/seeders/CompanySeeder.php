<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $unipersonal = Company::firstOrCreate(
            ['cuit' => '20-00000000-0'],
            [
                'name' => 'IPAC — Empresa Unipersonal',
                'short_name' => 'IPAC Unipersonal',
                'tax_condition' => 'IVA Responsable Inscripto',
                'address' => 'Neuquén',
                'city' => 'Neuquén',
                'state' => 'Neuquén',
                'is_active' => true,
            ]
        );

        $sociedad = Company::firstOrCreate(
            ['cuit' => '30-00000000-0'],
            [
                'name' => 'IPAC S.A.S.',
                'short_name' => 'IPAC SAS',
                'tax_condition' => 'IVA Responsable Inscripto',
                'address' => 'Neuquén',
                'city' => 'Neuquén',
                'state' => 'Neuquén',
                'is_active' => true,
            ]
        );

        $admin = User::role('admin')->first()
            ?? User::where('email', env('ADMIN_EMAIL', 'admin@admin'))->first();

        if ($admin) {
            $admin->companies()->syncWithoutDetaching([
                $unipersonal->id => ['is_default' => false],
                $sociedad->id => ['is_default' => true],
            ]);
        }
    }
}
