<?php

namespace App\Services;

use App\Models\Test;
use Illuminate\Support\Collection;

class QuoteItemChildrenSnapshotBuilder
{
    /**
     * @return array<int, array{test_id: int, name: string, depth: int}>
     */
    public function build(Test $parent): array
    {
        $result = [];
        $this->appendChildren($parent, 1, $result);

        return $result;
    }

    /**
     * @param  array<int, array{test_id: int, name: string, depth: int}>  $result
     */
    private function appendChildren(Test $parent, int $depth, array &$result): void
    {
        foreach ($this->orderedDirectChildren($parent) as $child) {
            $result[] = [
                'test_id' => $child->id,
                'name' => $this->formatName($child),
                'depth' => $depth,
            ];

            if ($this->hasNestedChildren($child)) {
                $this->appendChildren($child, $depth + 1, $result);
            }
        }
    }

    private function orderedDirectChildren(Test $parent): Collection
    {
        $children = $parent->childTests()
            ->orderBy('test_parents.order')
            ->get();

        $legacyChildren = $parent->children()->get();

        foreach ($legacyChildren as $legacyChild) {
            if (! $children->contains('id', $legacyChild->id)) {
                $children->push($legacyChild);
            }
        }

        return $children;
    }

    private function hasNestedChildren(Test $test): bool
    {
        return $test->childTests()->exists() || $test->children()->exists();
    }

    private function formatName(Test $test): string
    {
        if ($test->code) {
            return trim($test->code.' - '.$test->name);
        }

        return $test->name;
    }
}
