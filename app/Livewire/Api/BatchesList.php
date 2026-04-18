<?php

namespace App\Livewire\Api;

use App\Models\ApiClient;
use App\Services\Api\ApiMonitorService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BatchesList extends Component
{
    use WithPagination;

    #[Url(as: 'cliente')]
    public ?int $clientId = null;

    #[Url(as: 'desde')]
    public ?string $dateFrom = null;

    #[Url(as: 'hasta')]
    public ?string $dateTo = null;

    #[Url(as: 'solo_rechazos')]
    public bool $hasRejections = false;

    public function updatedClientId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedHasRejections(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['clientId', 'dateFrom', 'dateTo', 'hasRejections']);
        $this->resetPage();
    }

    public function render()
    {
        $batches = app(ApiMonitorService::class)->getBatches([
            'client_id' => $this->clientId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'has_rejections' => $this->hasRejections,
        ]);

        return view('livewire.api.batches-list', [
            'batches' => $batches,
            'clients' => ApiClient::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
