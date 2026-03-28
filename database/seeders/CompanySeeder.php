<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $unipersonal = Company::updateOrCreate(
            ['cuit' => '27-29145034-8'],
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

        $sociedad = Company::updateOrCreate(
            ['cuit' => '30-71922759-3'],
            [
                'name' => 'IPAC LABORATORIOS S.A.S.',
                'short_name' => 'IPAC SAS',
                'tax_condition' => 'IVA Responsable Inscripto',
                'address' => 'Leguizamon 356',
                'city' => 'Neuquén',
                'state' => 'Neuquén',
                'afip_cert_path' => 'customer_files/arca/labit-webs_5ddd63c20735f1f5.crt',
                'afip_key_path' => 'customer_files/arca/ipac_key.pem',
                'afip_production' => true,
                'is_active' => true,
            ]
        );

        PointOfSale::updateOrCreate(
            ['company_id' => $sociedad->id, 'afip_pos_number' => 2],
            [
                'code' => '00002',
                'name' => 'Leguizamon (Web Service)',
                'address' => 'Leguizamon 356 - Neuquen, Neuquen',
                'is_active' => true,
                'is_electronic' => true,
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
