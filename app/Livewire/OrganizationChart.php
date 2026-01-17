<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Job;

class OrganizationChart extends Component
{
    public $employees;
    public $job;
    public $currentEmployeeId = null;

    public function mount($employees, $job, $currentEmployeeId = null)
    {
        $this->employees = Employee::all();
        $this->job = $job = Job::whereNull('parent_id')->first();
        $this->currentEmployeeId = $currentEmployeeId;
    }

    public function render()
    {
        return view('livewire.organization-chart');
    }
}
