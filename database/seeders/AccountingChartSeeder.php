<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use Illuminate\Database\Seeder;

class AccountingChartSeeder extends Seeder
{
    public function run(): void
    {
        $chart = [
            ['code' => '1', 'name' => 'ACTIVO', 'type' => 'activo', 'level' => 1, 'is_header' => true],
            ['code' => '1.1', 'name' => 'ACTIVO CORRIENTE', 'type' => 'activo', 'level' => 2, 'is_header' => true, 'parent_code' => '1'],
            ['code' => '1.1.01', 'name' => 'Caja', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.02', 'name' => 'Bancos Cuenta Corriente', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.03', 'name' => 'Bancos Caja de Ahorro', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.04', 'name' => 'Deudores por Ventas', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.05', 'name' => 'Documentos a Cobrar', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.06', 'name' => 'IVA Crédito Fiscal', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.07', 'name' => 'Anticipos a Proveedores', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.1.08', 'name' => 'Otros Créditos', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.1'],
            ['code' => '1.2', 'name' => 'ACTIVO NO CORRIENTE', 'type' => 'activo', 'level' => 2, 'is_header' => true, 'parent_code' => '1'],
            ['code' => '1.2.01', 'name' => 'Bienes de Uso', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.2'],
            ['code' => '1.2.02', 'name' => 'Amortización Acumulada B.U.', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.2'],
            ['code' => '1.2.03', 'name' => 'Inversiones Permanentes', 'type' => 'activo', 'level' => 3, 'is_header' => false, 'parent_code' => '1.2'],

            ['code' => '2', 'name' => 'PASIVO', 'type' => 'pasivo', 'level' => 1, 'is_header' => true],
            ['code' => '2.1', 'name' => 'PASIVO CORRIENTE', 'type' => 'pasivo', 'level' => 2, 'is_header' => true, 'parent_code' => '2'],
            ['code' => '2.1.01', 'name' => 'Proveedores', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.02', 'name' => 'Documentos a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.03', 'name' => 'IVA Débito Fiscal', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.04', 'name' => 'Retenciones a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.05', 'name' => 'Percepciones a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.06', 'name' => 'Cargas Sociales a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.07', 'name' => 'Sueldos a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.08', 'name' => 'IIBB a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.09', 'name' => 'Anticipos de Clientes', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.10', 'name' => 'Otras Deudas', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.11', 'name' => 'Aportes patronales SUSS a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.1.12', 'name' => 'Contribuciones patronales SUSS a Pagar', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.1'],
            ['code' => '2.2', 'name' => 'PASIVO NO CORRIENTE', 'type' => 'pasivo', 'level' => 2, 'is_header' => true, 'parent_code' => '2'],
            ['code' => '2.2.01', 'name' => 'Préstamos Bancarios', 'type' => 'pasivo', 'level' => 3, 'is_header' => false, 'parent_code' => '2.2'],

            ['code' => '3', 'name' => 'PATRIMONIO NETO', 'type' => 'patrimonio_neto', 'level' => 1, 'is_header' => true],
            ['code' => '3.1', 'name' => 'CAPITAL', 'type' => 'patrimonio_neto', 'level' => 2, 'is_header' => true, 'parent_code' => '3'],
            ['code' => '3.1.01', 'name' => 'Capital Social', 'type' => 'patrimonio_neto', 'level' => 3, 'is_header' => false, 'parent_code' => '3.1'],
            ['code' => '3.1.02', 'name' => 'Aportes Irrevocables', 'type' => 'patrimonio_neto', 'level' => 3, 'is_header' => false, 'parent_code' => '3.1'],
            ['code' => '3.2', 'name' => 'RESULTADOS', 'type' => 'patrimonio_neto', 'level' => 2, 'is_header' => true, 'parent_code' => '3'],
            ['code' => '3.2.01', 'name' => 'Resultados Acumulados', 'type' => 'patrimonio_neto', 'level' => 3, 'is_header' => false, 'parent_code' => '3.2'],
            ['code' => '3.2.02', 'name' => 'Resultado del Ejercicio', 'type' => 'patrimonio_neto', 'level' => 3, 'is_header' => false, 'parent_code' => '3.2'],

            ['code' => '4', 'name' => 'INGRESOS', 'type' => 'resultado_positivo', 'level' => 1, 'is_header' => true],
            ['code' => '4.1', 'name' => 'INGRESOS OPERATIVOS', 'type' => 'resultado_positivo', 'level' => 2, 'is_header' => true, 'parent_code' => '4'],
            ['code' => '4.1.01', 'name' => 'Ventas', 'type' => 'resultado_positivo', 'level' => 3, 'is_header' => false, 'parent_code' => '4.1'],
            ['code' => '4.1.02', 'name' => 'Ingresos por Servicios', 'type' => 'resultado_positivo', 'level' => 3, 'is_header' => false, 'parent_code' => '4.1'],
            ['code' => '4.2', 'name' => 'OTROS INGRESOS', 'type' => 'resultado_positivo', 'level' => 2, 'is_header' => true, 'parent_code' => '4'],
            ['code' => '4.2.01', 'name' => 'Intereses Ganados', 'type' => 'resultado_positivo', 'level' => 3, 'is_header' => false, 'parent_code' => '4.2'],
            ['code' => '4.2.02', 'name' => 'Otros Ingresos', 'type' => 'resultado_positivo', 'level' => 3, 'is_header' => false, 'parent_code' => '4.2'],

            ['code' => '5', 'name' => 'EGRESOS', 'type' => 'resultado_negativo', 'level' => 1, 'is_header' => true],
            ['code' => '5.1', 'name' => 'COSTOS', 'type' => 'resultado_negativo', 'level' => 2, 'is_header' => true, 'parent_code' => '5'],
            ['code' => '5.1.01', 'name' => 'Costo de Mercaderías Vendidas', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.1'],
            ['code' => '5.1.02', 'name' => 'Costo de Servicios', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.1'],
            ['code' => '5.2', 'name' => 'GASTOS OPERATIVOS', 'type' => 'resultado_negativo', 'level' => 2, 'is_header' => true, 'parent_code' => '5'],
            ['code' => '5.2.01', 'name' => 'Sueldos y Jornales', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.02', 'name' => 'Cargas Sociales', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.03', 'name' => 'Honorarios Profesionales', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.04', 'name' => 'Alquileres', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.05', 'name' => 'Servicios (Luz, Gas, Tel)', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.06', 'name' => 'Seguros', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.07', 'name' => 'Impuestos y Tasas', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.08', 'name' => 'Gastos de Librería', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.09', 'name' => 'Gastos de Limpieza', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.10', 'name' => 'Amortizaciones', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.11', 'name' => 'Aportes patronales', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.2.12', 'name' => 'Contribuciones patronales', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.2'],
            ['code' => '5.3', 'name' => 'GASTOS FINANCIEROS', 'type' => 'resultado_negativo', 'level' => 2, 'is_header' => true, 'parent_code' => '5'],
            ['code' => '5.3.01', 'name' => 'Gastos Bancarios', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.3'],
            ['code' => '5.3.02', 'name' => 'Intereses Pagados', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.3'],
            ['code' => '5.3.03', 'name' => 'Comisiones Bancarias', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.3'],
            ['code' => '5.4', 'name' => 'OTROS EGRESOS', 'type' => 'resultado_negativo', 'level' => 2, 'is_header' => true, 'parent_code' => '5'],
            ['code' => '5.4.01', 'name' => 'Otros Egresos', 'type' => 'resultado_negativo', 'level' => 3, 'is_header' => false, 'parent_code' => '5.4'],
        ];

        foreach ($chart as $row) {
            $parentId = null;
            if (! empty($row['parent_code'])) {
                $parent = AccountingAccount::where('code', $row['parent_code'])->first();
                $parentId = $parent?->id;
            }

            AccountingAccount::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'parent_id' => $parentId,
                    'level' => $row['level'],
                    'is_header' => $row['is_header'],
                    'is_active' => true,
                ]
            );
        }
    }
}
