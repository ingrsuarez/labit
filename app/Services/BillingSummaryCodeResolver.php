<?php

namespace App\Services;

use App\Models\Test;
use Illuminate\Support\Collection;

class BillingSummaryCodeResolver
{
    /**
     * @param  Collection<int, object>  $lines
     * @param  callable(object): ?Test  $getTest
     * @param  callable(object): int  $getTestId
     * @param  callable(object): bool  $includeLine
     * @param  callable(object): float  $lineAmount
     * @return array{included: Collection<int, object>, codes_string: string, total_amount: float}
     */
    public function resolve(
        Collection $lines,
        callable $getTest,
        callable $getTestId,
        callable $includeLine,
        callable $lineAmount,
    ): array {
        $eligible = $lines->filter($includeLine)->values();

        if ($eligible->isEmpty()) {
            return [
                'included' => collect(),
                'codes_string' => '',
                'total_amount' => 0.0,
            ];
        }

        $presentTestIds = $eligible->map($getTestId)->unique()->values()->all();

        $included = $eligible->filter(function ($line) use ($getTest, $getTestId, $presentTestIds) {
            $test = $getTest($line);
            if (! $test) {
                return true;
            }

            $parentIds = $this->parentIdsForTest($test);

            foreach ($parentIds as $parentId) {
                if (in_array($parentId, $presentTestIds, true) && $parentId !== $getTestId($line)) {
                    return false;
                }
            }

            return true;
        })->values();

        $codes = $included
            ->map(function ($line) use ($getTest) {
                $test = $getTest($line);

                return $test?->code;
            })
            ->filter()
            ->sort()
            ->values();

        return [
            'included' => $included,
            'codes_string' => $codes->implode('-'),
            'total_amount' => round($included->sum(fn ($line) => $lineAmount($line)), 2),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function parentIdsForTest(Test $test): array
    {
        $ids = [];

        if ($test->relationLoaded('parentTests')) {
            $ids = $test->parentTests->pluck('id')->all();
        } elseif ($test->parent) {
            $ids[] = (int) $test->parent;
        } else {
            $ids = $test->parentTests()->pluck('parent_test_id')->all();
        }

        return array_map('intval', $ids);
    }
}
