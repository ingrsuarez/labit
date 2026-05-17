<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ProtocolStatusCalculator
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIALLY_VALIDATED = 'partially_validated';

    public const STATUS_VALIDATED = 'validated';

    /**
     * @param  iterable<object>  $items  Objetos con hasResult(): bool e is_validated (bool)
     */
    public function calculate(iterable $items): string
    {
        $collection = $items instanceof Collection ? $items : collect($items);
        $total = $collection->count();

        if ($total === 0) {
            return self::STATUS_PENDING;
        }

        $withResult = 0;
        $validated = 0;
        $empty = 0;

        foreach ($collection as $item) {
            $isValidated = (bool) ($item->is_validated ?? false);
            $hasResult = $isValidated || (method_exists($item, 'hasResult') && $item->hasResult());

            if ($isValidated) {
                $validated++;
            }

            if ($hasResult) {
                $withResult++;
            } else {
                $empty++;
            }
        }

        if ($withResult === 0) {
            return self::STATUS_PENDING;
        }

        if ($validated === $total) {
            return self::STATUS_VALIDATED;
        }

        if ($validated > 0) {
            $nonValidated = $collection->filter(fn ($item) => ! ($item->is_validated ?? false));
            if ($nonValidated->every(fn ($item) => method_exists($item, 'hasResult') && $item->hasResult())) {
                return self::STATUS_PARTIALLY_VALIDATED;
            }
        }

        if ($empty === 0 && $validated === 0) {
            return self::STATUS_COMPLETED;
        }

        if ($withResult > 0 && $empty > 0) {
            return self::STATUS_IN_PROGRESS;
        }

        return self::STATUS_PENDING;
    }

    /**
     * Excluye padres-título (agrupadores sin resultado propio con hijos cargados).
     *
     * @param  Collection<int, object>  $items
     * @param  callable(object): bool  $isTitleParent
     * @return Collection<int, object>
     */
    public function filterCountableDeterminations(
        Collection $items,
        callable $hasResult,
        callable $isValidated,
        callable $isTitleParent,
        ?callable $isExemptWhenEmpty = null,
    ): Collection {
        return $items->filter(function ($item) use ($hasResult, $isValidated, $isTitleParent, $isExemptWhenEmpty) {
            if ($isValidated($item) || $hasResult($item)) {
                return true;
            }

            if ($isExemptWhenEmpty && $isExemptWhenEmpty($item)) {
                return false;
            }

            return ! $isTitleParent($item);
        })->values();
    }

    public static function labels(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_PARTIALLY_VALIDATED => 'Validado parcial',
            self::STATUS_VALIDATED => 'Validado',
        ];
    }

    public static function colors(): array
    {
        return [
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_PARTIALLY_VALIDATED => 'indigo',
            self::STATUS_VALIDATED => 'purple',
        ];
    }

    public static function labelFor(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    public static function colorFor(string $status): string
    {
        return self::colors()[$status] ?? 'gray';
    }

    public static function isSent(?\DateTimeInterface $sentAt): bool
    {
        return $sentAt !== null;
    }
}
