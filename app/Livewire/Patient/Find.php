<?php

namespace App\Livewire\Patient;

use Livewire\Component;
use App\Models\Patient;

class Find extends Component
{
    public $name ="";
    public $dni;
    public $lastName = "";
    public $current_patient = "";
    public $patients = [];


    public function updateDni()
    {
        if($this->name <> ''){    
            $this->patients = Patient::whereRaw('lower(name) LIKE "%'.strtolower($this->name).'%"')->limit(15)->get(); 
        }elseif($this->lastName <> ''){
            $this->patients = Patient::whereRaw('lower(lastName) LIKE "%'.strtolower($this->lastName).'%"')->limit(15)->get(); 
        }elseif($this->dni <> ''){
            $this->patients = Patient::where('patientId','LIKE',$this->dni.'%')->limit(15)->get(); 
        }else
        {
            // $this->patients = "";
            return view('livewire.patient.find');
        
        }

    }
    // }
    // public function updateName()
    // {
    //     if($this->name <> ''){    
    //         $this->patients = Patient::whereRaw('lower(name) LIKE "%'.strtolower($this->name).'%"')->limit(15)->get(); 
    //     }
    // }
    // public function updateDni()
    // {
    //     if($this->name <> '')
    //     {
    //         $this->patients = Patient::where('patientId', 'like', $this->dni . '%')->get();
    //     }
    // }

    public function render()
    {
        // dd($this->dni);
        return view('livewire.patient.find');
    }
}
