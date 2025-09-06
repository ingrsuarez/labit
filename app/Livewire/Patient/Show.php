<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use App\Models\Patient;

class Show extends Component
{
    public $name;
    public $dni;
    public $lastName;
    public $current_patient;
    public $patients;
    
    // public function mount()
    // {
    //     $this->patients = Patient::all();
        
    // }

    public function updateDni()
    {
        if($this->name <> ''){    
            $this->patients = Patient::whereRaw('lower(name) LIKE "%'.strtolower($this->name).'%"')->limit(15)->get(); 

        }elseif($this->lastName <> ''){
            $this->patients = Patient::whereRaw('lower(lastName) LIKE "%'.strtolower($this->lastName).'%"')->limit(15)->get(); 


        }elseif($this->dni <> ''){

            $this->patients = Patient::where('patientId','LIKE',$this->dni.'%')->limit(15)->get(); 
        }


    }
    public function render()
    {
        return view('livewire.patient.show');
    }
}
