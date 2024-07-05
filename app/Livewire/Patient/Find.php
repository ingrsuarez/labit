<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use App\Models\Patient;

class Find extends Component
{
    public $name;
    public $dni;
    public $lastName;
    public $current_patient;
    
    public function render()
    {
        return view('livewire.patient.find');
    }
}
