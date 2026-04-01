<?php

namespace App\Services;

use App\Models\BankMovement;
use App\Models\BankStatement;
use App\Models\CollectionReceipt;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationService
{
    public function autoReconcile(BankStatement $statement): array
    {
        $movements = $statement->movements()
            ->where('reconciliation_status', 'pending')
            ->get();

        $matched = 0;
        $suggestions = [];

        foreach ($movements as $movement) {
            $result = $this->findMatch($movement, $statement->bankAccount->company_id);

            if ($result['confidence'] === 'exact') {
                $this->linkMovement($movement, $result['record'], auth()->user());
                $matched++;
            } elseif ($result['confidence'] === 'probable') {
                $suggestions[] = [
                    'movement_id' => $movement->id,
                    'record_type' => get_class($result['record']),
                    'record_id' => $result['record']->id,
                    'record_label' => $result['label'],
                    'reason' => $result['reason'],
                ];
            }
        }

        return [
            'matched' => $matched,
            'pending' => $movements->count() - $matched,
            'suggestions' => $suggestions,
        ];
    }

    private function findMatch(BankMovement $movement, int $companyId): array
    {
        $date = $movement->date;

        if ($movement->is_debit) {
            $amount = (float) $movement->debit;

            $po = PaymentOrder::where('company_id', $companyId)
                ->whereIn('status', ['pagada', 'aprobada'])
                ->where('total', $amount)
                ->whereBetween('date', [$date->copy()->subDays(3), $date->copy()->addDays(3)])
                ->whereDoesntHave('reconciledMovements')
                ->first();

            if ($po) {
                $exact = $po->date->isSameDay($date);

                return [
                    'confidence' => $exact ? 'exact' : 'probable',
                    'record' => $po,
                    'label' => "OP {$po->number} — {$po->supplier->name} — $".number_format($po->total, 2, ',', '.'),
                    'reason' => $exact ? 'Monto y fecha exactos' : 'Monto exacto, fecha cercana',
                ];
            }
        }

        if ($movement->is_credit) {
            $amount = (float) $movement->credit;

            $cr = CollectionReceipt::where('company_id', $companyId)
                ->where('status', 'confirmado')
                ->where('total', $amount)
                ->whereBetween('date', [$date->copy()->subDays(3), $date->copy()->addDays(3)])
                ->whereDoesntHave('reconciledMovements')
                ->first();

            if ($cr) {
                $exact = $cr->date->isSameDay($date);

                return [
                    'confidence' => $exact ? 'exact' : 'probable',
                    'record' => $cr,
                    'label' => "RC {$cr->number} — {$cr->customer->name} — $".number_format($cr->total, 2, ',', '.'),
                    'reason' => $exact ? 'Monto y fecha exactos' : 'Monto exacto, fecha cercana',
                ];
            }
        }

        return ['confidence' => 'none', 'record' => null, 'label' => null, 'reason' => null];
    }

    public function linkMovement(BankMovement $movement, Model $record, User $user): void
    {
        $movement->update([
            'reconciliation_status' => 'matched',
            'reconciled_type' => get_class($record),
            'reconciled_id' => $record->id,
            'reconciled_at' => now(),
            'reconciled_by' => $user->id,
        ]);
    }

    public function unlinkMovement(BankMovement $movement): void
    {
        $movement->update([
            'reconciliation_status' => 'pending',
            'reconciled_type' => null,
            'reconciled_id' => null,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    public function ignoreMovement(BankMovement $movement, User $user, ?string $notes = null): void
    {
        $movement->update([
            'reconciliation_status' => 'ignored',
            'reconciled_at' => now(),
            'reconciled_by' => $user->id,
            'notes' => $notes,
        ]);
    }
}
