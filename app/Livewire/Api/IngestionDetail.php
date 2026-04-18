<?php

namespace App\Livewire\Api;

use App\Models\ResultIngestion;
use App\Services\Api\ApiMonitorService;
use Livewire\Component;

class IngestionDetail extends Component
{
    public ResultIngestion $ingestion;

    public function mount(ResultIngestion $ingestion): void
    {
        $this->ingestion = $ingestion->load(['batch.apiClient.labBranch']);
    }

    public function render()
    {
        $protocolUrl = app(ApiMonitorService::class)->getProtocolUrl(
            $this->ingestion->protocol_number,
            $this->ingestion->protocol_type
        );

        return view('livewire.api.ingestion-detail', [
            'protocolUrl' => $protocolUrl,
        ])->layout('layouts.app');
    }
}
