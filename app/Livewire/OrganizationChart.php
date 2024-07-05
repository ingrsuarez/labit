<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Job;

class OrganizationChart extends Component
{
    public $employees;
    public $job;

    public function mount($employees, $job)
    {
        $this->employees = Employee::all();
        $this->job = $job = Job::whereNull('parent_id')->first();
    }

    public function render()
    {
        return view('livewire.organization-chart');
    }
}
