<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\SupplyLabBranchStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyStockService
{
    /**
     * @param  array<string, mixed>  $payload  reason, reference_type, reference_id, notes, user_id, lot_number?, expiration_date?
     */
    public function recordEntrada(Supply $supply, ?int $labBranchId, float $quantity, array $payload): void
    {
        $this->assertPositiveQty($quantity);

        if ($labBranchId === null) {
            $this->legacyEntrada($supply, $quantity, $payload);

            return;
        }

        DB::transaction(function () use ($supply, $labBranchId, $quantity, $payload) {
            $row = $this->lockBranchRow($supply->id, $labBranchId);
            $previous = (float) $row->quantity;
            $new = $previous + $quantity;
            $row->quantity = $new;
            $row->save();

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => $labBranchId,
                'type' => 'entrada',
                'quantity' => $quantity,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $this->syncSupplyCachedTotal($supply->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordSalida(Supply $supply, ?int $labBranchId, float $quantity, array $payload): void
    {
        $this->assertPositiveQty($quantity);

        if ($labBranchId === null) {
            $this->legacySalida($supply, $quantity, $payload);

            return;
        }

        DB::transaction(function () use ($supply, $labBranchId, $quantity, $payload) {
            $row = $this->lockBranchRow($supply->id, $labBranchId);
            $previous = (float) $row->quantity;
            if ($previous < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'No hay suficiente stock en la sede. Disponible: '.$previous,
                ]);
            }
            $new = $previous - $quantity;
            $row->quantity = $new;
            $row->save();

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => $labBranchId,
                'type' => 'salida',
                'quantity' => $quantity,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $this->syncSupplyCachedTotal($supply->fresh());
        });
    }

    /**
     * Ajuste manual: el valor ingresado es el stock absoluto en esa sede (misma semántica que antes a nivel global).
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordAjuste(Supply $supply, ?int $labBranchId, float $newAbsoluteQuantity, array $payload): void
    {
        if ($labBranchId === null) {
            $this->legacyAjuste($supply, $newAbsoluteQuantity, $payload);

            return;
        }

        DB::transaction(function () use ($supply, $labBranchId, $newAbsoluteQuantity, $payload) {
            $row = $this->lockBranchRow($supply->id, $labBranchId);
            $previous = (float) $row->quantity;
            $new = max(0, $newAbsoluteQuantity);
            $row->quantity = $new;
            $row->save();

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => $labBranchId,
                'type' => 'ajuste',
                'quantity' => $new,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $this->syncSupplyCachedTotal($supply->fresh());
        });
    }

    public function revertMovementEffect(StockMovement $movement): void
    {
        $supply = $movement->supply;
        if (! $supply) {
            return;
        }

        if ($movement->lab_branch_id) {
            DB::transaction(function () use ($movement, $supply) {
                $row = SupplyLabBranchStock::query()
                    ->where('supply_id', $supply->id)
                    ->where('lab_branch_id', $movement->lab_branch_id)
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    if ($movement->type === 'entrada') {
                        $row->quantity = max(0, (float) $row->quantity - (float) $movement->quantity);
                    } elseif ($movement->type === 'salida') {
                        $row->quantity = (float) $row->quantity + (float) $movement->quantity;
                    } else {
                        $row->quantity = max(0, (float) $movement->previous_stock);
                    }
                    $row->save();
                }

                $this->syncSupplyCachedTotal($supply->fresh());
            });
        } else {
            if ($movement->type === 'entrada') {
                $supply->decrement('stock', $movement->quantity);
            } elseif ($movement->type === 'salida') {
                $supply->increment('stock', $movement->quantity);
            } else {
                $supply->update(['stock' => $movement->previous_stock]);
            }
        }
    }

    /**
     * Elimina movimientos por referencia y revierte su efecto en stock (pivot o legado).
     */
    public function deleteMovementsForReference(string $referenceType, int $referenceId): void
    {
        $movements = StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();

        foreach ($movements as $movement) {
            $this->revertMovementEffect($movement);
            $movement->delete();
        }
    }

    public function syncSupplyCachedTotal(Supply $supply): void
    {
        $sum = (float) SupplyLabBranchStock::query()->where('supply_id', $supply->id)->sum('quantity');
        $supply->update(['stock' => $sum]);
    }

    protected function assertPositiveQty(float $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad debe ser mayor a 0.',
            ]);
        }
    }

    protected function lockBranchRow(int $supplyId, int $branchId): SupplyLabBranchStock
    {
        $row = SupplyLabBranchStock::query()
            ->where('supply_id', $supplyId)
            ->where('lab_branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            SupplyLabBranchStock::query()->create([
                'supply_id' => $supplyId,
                'lab_branch_id' => $branchId,
                'quantity' => 0,
            ]);

            $row = SupplyLabBranchStock::query()
                ->where('supply_id', $supplyId)
                ->where('lab_branch_id', $branchId)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function legacyEntrada(Supply $supply, float $quantity, array $payload): void
    {
        DB::transaction(function () use ($supply, $quantity, $payload) {
            $supply->refresh();
            $previous = (float) $supply->stock;
            $new = $previous + $quantity;

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => null,
                'type' => 'entrada',
                'quantity' => $quantity,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $supply->update(['stock' => $new]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function legacySalida(Supply $supply, float $quantity, array $payload): void
    {
        DB::transaction(function () use ($supply, $quantity, $payload) {
            $supply->refresh();
            $previous = (float) $supply->stock;
            if ($previous < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'No hay suficiente stock. Stock actual: '.$previous,
                ]);
            }
            $new = $previous - $quantity;

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => null,
                'type' => 'salida',
                'quantity' => $quantity,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $supply->update(['stock' => $new]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function legacyAjuste(Supply $supply, float $newAbsoluteQuantity, array $payload): void
    {
        DB::transaction(function () use ($supply, $newAbsoluteQuantity, $payload) {
            $supply->refresh();
            $previous = (float) $supply->stock;
            $new = max(0, $newAbsoluteQuantity);

            StockMovement::create(array_merge($payload, [
                'supply_id' => $supply->id,
                'lab_branch_id' => null,
                'type' => 'ajuste',
                'quantity' => $new,
                'previous_stock' => $previous,
                'new_stock' => $new,
            ]));

            $supply->update(['stock' => $new]);
        });
    }
}
