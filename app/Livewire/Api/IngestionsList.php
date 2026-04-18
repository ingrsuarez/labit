<?php

namespace App\Livewire\Api;

use App\Models\ApiClient;
use App\Services\Api\ApiMonitorService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IngestionsList extends Component
{
    use WithPagination;

    #[Url(as: 'estado')]
    public ?string $status = null;

    #[Url(as: 'razon')]
    public ?string $rejectionReason = null;

    #[Url(as: 'protocolo')]
    public ?string $protocolNumber = null;

    #[Url(as: 'cliente')]
    public ?int $clientId = null;

    #[Url(as: 'desde')]
    public ?string $dateFrom = null;

    #[Url(as: 'equipo')]
    public ?string $equipmentName = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRejectionReason(): void
    {
        $this->resetPage();
    }

    public function updatedProtocolNumber(): void
    {
        $this->resetPage();
    }

    public function updatedClientId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedEquipmentName(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['status', 'rejectionReason', 'protocolNumber', 'clientId', 'dateFrom', 'equipmentName']);
        $this->resetPage();
    }

    public function render()
    {
        $ingestions = app(ApiMonitorService::class)->getIngestions([
            'status' => $this->status,
            'rejection_reason' => $this->rejectionReason,
            'protocol_number' => $this->protocolNumber,
            'client_id' => $this->clientId,
            'date_from' => $this->dateFrom,
            'equipment_name' => $this->equipmentName,
        ]);

        return view('livewire.api.ingestions-list', [
            'ingestions' => $ingestions,
            'clients' => ApiClient::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
