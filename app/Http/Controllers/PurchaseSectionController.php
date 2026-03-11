<?php

namespace App\Http\Controllers;

class PurchaseSectionController extends Controller
{
    public function index()
    {
        $this->authorize('compras.section');
        $section = [
            'title' => 'Compras',
            'description' => 'Gestión de proveedores, insumos, stock y órdenes de compra',
            'items' => [
                [
                    'name' => 'Proveedores',
                    'description' => 'Gestión de proveedores',
                    'route' => route('suppliers.index'),
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
                [
                    'name' => 'Insumos',
                    'description' => 'Insumos y control de stock',
                    'route' => route('supplies.index'),
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
                [
                    'name' => 'Categorías de Insumos',
                    'description' => 'Clasificación de insumos',
                    'route' => route('supply-categories.index'),
                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                ],
                [
                    'name' => 'Movimientos de Stock',
                    'description' => 'Historial de entradas y salidas',
                    'route' => route('stock-movements.index'),
                    'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
                ],
                [
                    'name' => 'Solicitudes de Cotización',
                    'description' => 'Pedidos de cotización a proveedores',
                    'route' => route('purchase-quotation-requests.index'),
                    'icon' => 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z',
                ],
                [
                    'name' => 'Órdenes de Compra',
                    'description' => 'Órdenes de compra a proveedores',
                    'route' => route('purchase-orders.index'),
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'name' => 'Remitos',
                    'description' => 'Recepción de mercadería',
                    'route' => route('delivery-notes.index'),
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                ],
                [
                    'name' => 'Facturas de Compra',
                    'description' => 'Facturas y saldos de proveedores',
                    'route' => route('purchase-invoices.index'),
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'name' => 'Órdenes de Pago',
                    'description' => 'Pagos a proveedores',
                    'route' => route('payment-orders.index'),
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }
}
