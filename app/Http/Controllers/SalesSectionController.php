<?php

namespace App\Http\Controllers;

class SalesSectionController extends Controller
{
    public function index()
    {
        $this->authorize('ventas.section');
        $section = [
            'title' => 'Ventas',
            'description' => 'Gestión de facturación y cobranzas',
            'items' => [
                [
                    'name' => 'Clientes',
                    'description' => 'Gestión de clientes',
                    'route' => route('customer.index'),
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'name' => 'Presupuestos',
                    'description' => 'Presupuestos y cotizaciones',
                    'route' => route('quotes.index'),
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                ],
                [
                    'name' => 'Facturas de Venta',
                    'description' => 'Facturación y saldos de clientes',
                    'route' => route('sales-invoices.index'),
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'name' => 'Recibos de Cobro',
                    'description' => 'Cobranzas a clientes',
                    'route' => route('collection-receipts.index'),
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'name' => 'Notas de Crédito',
                    'description' => 'Notas de crédito emitidas',
                    'route' => route('credit-notes.index'),
                    'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
                ],
                [
                    'name' => 'Puntos de Venta',
                    'description' => 'Administración de puntos de venta',
                    'route' => route('points-of-sale.index'),
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }
}
