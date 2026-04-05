<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\StockMovement;

class DeliveryNoteStockService
{
    /**
     * Revierte todos los movimientos de stock de un remito (para delete).
     * Decrementamos el stock de cada insumo y eliminamos los StockMovements.
     */
    public function reverseStockForDeletion(DeliveryNote $deliveryNote): void
    {
        $movements = StockMovement::where('reference_type', DeliveryNote::class)
            ->where('reference_id', $deliveryNote->id)
            ->with('supply')
            ->get();

        foreach ($movements as $movement) {
            if ($movement->supply) {
                $newStock = max(0, (float) $movement->supply->stock - (float) $movement->quantity);
                $movement->supply->update(['stock' => $newStock]);
            }
        }

        StockMovement::where('reference_type', DeliveryNote::class)
            ->where('reference_id', $deliveryNote->id)
            ->delete();
    }

    /**
     * Sincroniza el stock al editar un remito aceptado.
     * Revierte los movimientos anteriores y recrea desde los ítems actuales.
     */
    public function syncStockAfterUpdate(DeliveryNote $deliveryNote): void
    {
        $this->reverseStockForDeletion($deliveryNote);

        $newItems = $deliveryNote->items()->with('supply')->get();

        foreach ($newItems as $item) {
            if ((int) $item->quantity_received <= 0 || ! $item->supply) {
                continue;
            }

            $supply = $item->supply;
            $previousStock = (float) $supply->stock;
            $newStock = $previousStock + (float) $item->quantity_received;

            StockMovement::create([
                'supply_id' => $supply->id,
                'type' => 'entrada',
                'quantity' => $item->quantity_received,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => 'compra',
                'lot_number' => $item->lot_number,
                'expiration_date' => $item->expiration_date?->format('Y-m-d'),
                'reference_type' => DeliveryNote::class,
                'reference_id' => $deliveryNote->id,
                'notes' => "Remito #{$deliveryNote->remito_number} (editado)",
                'user_id' => auth()->id(),
            ]);

            $supply->update(['stock' => $newStock]);
        }
    }
}
