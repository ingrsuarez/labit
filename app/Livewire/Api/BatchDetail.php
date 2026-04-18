<?php

namespace App\Livewire\Api;

use App\Models\ResultBatch;
use Livewire\Component;

class BatchDetail extends Component
{
    public ResultBatch $batch;

    public function mount(ResultBatch $batch): void
    {
        $this->batch = $batch->load(['apiClient.labBranch', 'ingestions']);
    }

    public function render()
    {
        return view('livewire.api.batch-detail')
            ->layout('layouts.app');
    }
}
