<?php

namespace App\Support;

use App\Enums\DeterminationProfileLabType;
use App\Models\Test;

class DeterminationProfileTestMatcher
{
    public static function matches(Test $test, DeterminationProfileLabType $type): bool
    {
        $key = $type->categoryKey();
        $cats = $test->categories ?? [];
        if (is_array($cats) && in_array($key, $cats, true)) {
            return true;
        }

        $test->loadMissing(['parentTests', 'parentTest']);

        foreach ($test->parentTests as $parent) {
            $pcats = $parent->categories ?? [];
            if (is_array($pcats) && in_array($key, $pcats, true)) {
                return true;
            }
        }

        $legacyParent = $test->parentTest;
        if ($legacyParent) {
            $lcats = $legacyParent->categories ?? [];

            return is_array($lcats) && in_array($key, $lcats, true);
        }

        return false;
    }
}
