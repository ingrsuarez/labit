<?php

namespace App\Services;

use App\Models\DeliveryNote;

class DeliveryNoteStockService
{
    public function __construct(
        protected SupplyStockService $supplyStockService
    ) {}

    /**
     * Revierte todos los movimientos de stock de un remito (para delete).
     */
    public function reverseStockForDeletion(DeliveryNote $deliveryNote): void
    {
        $this->supplyStockService->deleteMovementsForReference(DeliveryNote::class, $deliveryNote->id);
    }

    /**
     * Sincroniza el stock al editar un remito aceptado.
     * Revierte los movimientos anteriores y recrea desde los ítems actuales.
     */
    public function syncStockAfterUpdate(DeliveryNote $deliveryNote): void
    {
        $this->reverseStockForDeletion($deliveryNote);

        $branch = LabBranchResolver::requireActiveBranchForStock($deliveryNote->lab_branch_id);

        $newItems = $deliveryNote->items()->with('supply')->get();

        foreach ($newItems as $item) {
            if ((int) $item->quantity_received <= 0 || ! $item->supply) {
                continue;
            }

            $supply = $item->supply;

            $this->supplyStockService->recordEntrada($supply, $branch->id, (float) $item->quantity_received, [
                'reason' => 'compra',
                'lot_number' => $item->lot_number,
                'expiration_date' => $item->expiration_date,
                'reference_type' => DeliveryNote::class,
                'reference_id' => $deliveryNote->id,
                'notes' => "Remito #{$deliveryNote->remito_number} (editado)",
                'user_id' => auth()->id(),
            ]);

            $updateData = [];
            if ($item->purchaseOrderItem && $item->purchaseOrderItem->unit_price) {
                $updateData['last_price'] = $item->purchaseOrderItem->unit_price;
            }
            if ($updateData !== []) {
                $supply->refresh()->update($updateData);
            }
        }
    }
}
