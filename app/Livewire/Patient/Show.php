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
    
    public function render()
    {
        if($this->name <> ''){    
            $patients = Patient::whereRaw('lower(name) LIKE "'.strtolower($this->name).'%"')->paginate(3); 
            return view('livewire.patient.show',compact('patients'));
        }elseif($this->lastName <> ''){
            $patients = Patient::whereRaw('lower(lastName) LIKE "'.strtolower($this->lastName).'%"')->paginate(3); 
            return view('',compact('patients'));

        }elseif($this->dni <> ''){
            $patients = Patient::where('patientId','LIKE',$this->dni.'%')->paginate(3); 
            return view('',compact('patients'));

        }else
        {

            $patients = [];
            return view('',compact('patients'));
        
        }
        return view('');
    }
}
