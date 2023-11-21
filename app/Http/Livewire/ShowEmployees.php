<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Employee;

class ShowEmployees extends Component
{
    public $employees;

    public function mount($employees)
    {
        $this->employees =  $employees = Employee::all();
    }

    public function render()
    {
        return view('livewire.show-employees');
    }
}
