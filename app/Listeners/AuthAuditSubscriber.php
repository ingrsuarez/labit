<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthAuditSubscriber
{
    public function handleLogin(Login $event): void
    {
        AuditLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'action' => 'login',
            'description' => 'Inició sesión',
            'ip_address' => request()?->ip(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'action' => 'logout',
            'description' => 'Cerró sesión',
            'ip_address' => request()?->ip(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        AuditLog::create([
            'user_id' => null,
            'user_name' => $event->credentials['email'] ?? 'desconocido',
            'action' => 'login_failed',
            'description' => 'Intento de login fallido con email: '.($event->credentials['email'] ?? '?'),
            'ip_address' => request()?->ip(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
