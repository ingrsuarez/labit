<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public function logAudit(string $action, string $description, ?int $userId = null): AuditLog
    {
        $user = $userId ? \App\Models\User::find($userId) : auth()->user();

        return $this->auditLogs()->create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'Sistema',
            'action' => $action,
            'description' => $description,
            'ip_address' => request()?->ip(),
        ]);
    }
}
