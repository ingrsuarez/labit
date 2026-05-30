<?php

namespace App\Services;

use App\Models\UserNavigationStat;

class NavigationAccessService
{
    public function recordHit(int $userId, string $shortcutKey): void
    {
        $stat = UserNavigationStat::firstOrCreate(
            [
                'user_id' => $userId,
                'shortcut_key' => $shortcutKey,
            ],
            [
                'hit_count' => 0,
                'last_accessed_at' => null,
            ]
        );

        $stat->increment('hit_count');
        $stat->update(['last_accessed_at' => now()]);
    }
}
