<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Sample;
use App\Models\VetAdmission;
use Illuminate\Database\Eloquent\Model;

class LogsProtocolDelivery
{
    public static function logResultDeliveredOncePerDay(Model $protocol, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        if (! $userId || ! self::protocolHasValidatedResults($protocol)) {
            return;
        }

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $exists = AuditLog::query()
            ->where('user_id', $userId)
            ->where('action', 'result_delivered')
            ->where('auditable_type', $protocol->getMorphClass())
            ->where('auditable_id', $protocol->getKey())
            ->whereBetween('created_at', [$start, $end])
            ->exists();

        if ($exists) {
            return;
        }

        $number = $protocol->protocol_number ?? (string) $protocol->getKey();
        $protocol->logAudit('result_delivered', 'Entregó resultado del protocolo Nº '.$number, $userId);
    }

    public static function protocolHasValidatedResults(Model $protocol): bool
    {
        return match ($protocol::class) {
            Admission::class => $protocol->admissionTests()->where('is_validated', true)->exists(),
            VetAdmission::class => $protocol->vetTests()->where('is_validated', true)->exists(),
            Sample::class => $protocol->determinations()->where('is_validated', true)->exists(),
            default => false,
        };
    }
}
