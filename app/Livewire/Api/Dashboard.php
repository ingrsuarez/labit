<?php

namespace App\Livewire\Api;

use App\Services\Api\ApiMonitorService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public int $periodDays = 1;

    #[Computed]
    public function counters(): array
    {
        return app(ApiMonitorService::class)->getCounters($this->periodDays);
    }

    #[Computed]
    public function clientsStatus(): array
    {
        return app(ApiMonitorService::class)->getClientsStatus(7);
    }

    public function render()
    {
        return view('livewire.api.dashboard')
            ->layout('layouts.app');
    }
}
